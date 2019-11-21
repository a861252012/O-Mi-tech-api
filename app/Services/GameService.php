<?php
/**
 * 遊戲 服務
 * @author Weine
 * @date 2019/11/12
 */

namespace App\Services;

use App\Facades\SiteSer;
use App\Repositories\SiteConfigsRepository;
use App\Repositories\UserAccRepository;
use App\Traits\CurlAdapter;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class GameService
{
	use CurlAdapter;

	// HQT HOST
	private $host;

	// HQT渠道id
	private $channelId;

	// HQT私有key
	private $privateKey;

	// HQT帳號前綴
	private $gameAcctPrefix;

	private $username;

	private $password;

	protected $userAccRepository;
	protected $siteConfigsRepository;

	public function __construct(
		UserAccRepository $userAccRepository,
		SiteConfigsRepository $siteConfigsRepository
	){
		$this->userAccRepository = $userAccRepository;
		$this->siteConfigsRepository = $siteConfigsRepository;
	}

	/* 創建簽名 */
	private function makeSign($data)
	{
		ksort($data);
		$data['privatekey'] = $this->privateKey;

		$signArr = [];
		foreach($data as $k => $v) {
			$signArr[] = "{$k}=>{$v}";
		}

		$signStr = implode('&', $signArr);

		return md5($signStr);
	}

	/* 產生帳號 */
	private function genAccount()
	{
		$tempAcc = Str::random(4) . explode(' ',microtime())[1];
		return $this->gameAcctPrefix . $tempAcc;
	}

	/* 產生密碼 */
	private function genPassword()
	{
		return Str::random(20);
	}

	/* 產生交易單號 */
	private function genOrdNo()
	{
		$prifix = 'VORD';
		$ordNo = $prifix . time() . rand(0,9);
		return $ordNo;
	}

	/* 解碼回應 */
	private function decRes($response)
	{
	    $r = json_encode(json_decode($response), JSON_UNESCAPED_UNICODE);
		info("API raw 回應: " . $r);

		$r = json_decode($r, true);

		/* 更新log時間 */
		$this->userAccRepository->updateAcc(['uid' => auth()->id(), 'platform' => 'HQT'],['response' => $r['errcode']]);

		if(empty($r)
			|| !is_array($r)
			|| !Arr::has($r, 'errcode')
			|| !empty($r['errcode'])
		) {
			return false;
		}

		return $r;
	}

	/* 檢查設定 */
	public function checkSetting()
	{
		/* 先在redis取得設定 */
		$status = Redis::get('hqt_game_status');
		$settings = json_decode(Redis::get('hqt_game_setting'));

		/* 如取不到，則到資料庫取得 */
		if(is_null($status) || !is_bool($status) || empty($settings)) {
			/* 刪除原有key */
			Redis::del('hqt_game_status');
			Redis::del('hqt_game_setting');

			$config = $this->siteConfigsRepository->getSettingByHQT();
			if(empty($config)) {
				Log::error("資料庫無法取得設定");
				return false;
			}

			collect(json_decode($config['hqt_game_setting']))->map(function($item, $key) {
				return $this->{camel_case($key)} = $item;
			});

			/* 建立redis設定 */
			Redis::set('hqt_game_status', $config['hqt_game_status']);
			Redis::set('hqt_game_setting', $config['hqt_game_setting']);

			$status = $config['hqt_game_status'];
		}

		return (boolean) ($status ?? false);
	}

	/* 創建遊戲帳號 */
	private function createAcc()
	{
		info("創建遊戲帳號");
		$param = [
			'action' => 'createuser',
			'channel' => $this->channelId,
			'password' => $this->genPassword(),
			'username' => $this->genAccount(),
		];

		/* 簽名 */
		$sign = $this->makeSign($param);
		$param['sign'] = $sign;

		$apiUrl = $this->host . "?" . http_build_query($param);
		info("URL: " . $apiUrl);

		$apiResponse = $this->decRes($this->get($apiUrl)->getBody()->getContents());

		if(empty($apiResponse)) {
			Log::error("創建帳號失敗");
			return false;
		}

		/* 新增至DB */
		$insertData = [
			'uid' 		=> auth()->id(),
			'platform' 	=> 'HQT',
			'ref_type' 	=> 1,
			'ref_acc' 	=> $param['username'],
			'ref_pwd' 	=> $param['password'],
		];

		if(empty($this->userAccRepository->insertAcc($insertData))) {
			return false;
		}

		$this->username = $param['username'];
		$this->password = $param['password'];

		return true;
	}

	/* 登錄 */
	public function login()
	{
		/* 取得遊戲對應帳密 */
		$user = $this->userAccRepository->getByUid(auth()->id());

		/* 如查無對應帳號，則創建 */
		if($user->isEmpty()) {
			if(!$this->createAcc()) {
				return false;
			}
		} else {
			$acct = $user->where('platform','HQT')
						->all()[0]
						->only(['ref_acc', 'ref_pwd']);

			$this->username = $acct['ref_acc'];
			$this->password = $acct['ref_pwd'];
		}

		/* 登錄遊戲 */
		info("登錄遊戲");
		$param = [
			'action' 	=> 'login',
			'channel' 	=> $this->channelId,
			'gamecode' 	=> '',
			'loadbg' 	=> '',
			'password' 	=> $this->password,
			'username' 	=> $this->username,
			'tableid' 	=> '',
		];

		/* 簽名 */
		$sign = $this->makeSign($param);
		$param['sign'] = $sign;

		$apiUrl = $this->host . "?" . http_build_query($param);
		info("URL: " . $apiUrl);

		$apiResponse = $this->decRes($this->get($apiUrl)->getBody()->getContents());

		if(empty($apiResponse)) {
			Log::error("登錄遊戲失敗 - API回應不正確");
			return false;
		}

		if(!Arr::has($apiResponse, 'result')) {
			Log::error("登錄遊戲失敗 - API回應缺少參數 'result' ");
			return false;
		}

		return $apiResponse;
	}

	/* 儲值 */
	public function deposit(int $amount)
	{
		/* 取得遊戲對應帳密 */
		$user = $this->userAccRepository->getByUid(auth()->id());

		/* 如查無對應帳號，則創建 */
		if($user->isEmpty()) {
			if(!$this->createAcc()) {
				return false;
			}
		} else {
			$acct = $user->where('platform','HQT')
				->all()[0]
				->only(['ref_acc', 'ref_pwd']);

			$this->username = $acct['ref_acc'];
			$this->password = $acct['ref_pwd'];
		}

		/* 儲值 */
		info("儲值");
		$param = [
			'action' 	=> 'deposit',
			'channel' 	=> $this->channelId,
			'password' 	=> $this->password,
			'username' 	=> $this->username,
			'amount'	=> $amount,
			'orderno'	=> $this->genOrdNo(),
		];

		/* 簽名 */
		$sign = $this->makeSign($param);
		$param['sign'] = $sign;

		$apiUrl = $this->host . "?" . http_build_query($param);
		info("URL: " . $apiUrl);

		$apiResponse = $this->decRes($this->get($apiUrl)->getBody()->getContents());

		if(empty($apiResponse)) {
			Log::error("儲值失敗 - API回應不正確");
			return false;
		}

		if(!Arr::has($apiResponse, 'balance')) {
			Log::error("儲值失敗 - API回應缺少參數 'balance' ");
			return false;
		}

		return $apiResponse;
	}
}
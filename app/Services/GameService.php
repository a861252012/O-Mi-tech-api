<?php
/**
 * 遊戲 服務
 * @author Weine
 * @date 2019/11/12
 */

namespace App\Services;

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

	const HQT_HOST = 'http://10.1.101.120:9999/eas/api/rest';

	// HQT渠道id
	const HQT_CHANNEL = 50046;

	// HQT私有key
	const HQT_PRIVATEKEY = '500461573624188dbo136';

	// HQT帳號前綴
	const HQT_PREFIX = 'f_';

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
		$data['privatekey'] = self::HQT_PRIVATEKEY;

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
		return self::HQT_PREFIX . $tempAcc;
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
		$hqtSetting = Redis::get('hqt_game_status');
		if(empty($hqtSetting) || !is_bool($hqtSetting)) {
			$config = $this->siteConfigsRepository->getByCondition(['site_id' => 1, 'k' => 'hqt_game_status']);
			$hqtSetting = $config->v;
			Redis::set('hqt_game_status', $hqtSetting);
		}

		return (boolean) ($hqtSetting ?? false);
	}

	/* 創建遊戲帳號 */
	private function createAcc()
	{
		info("創建遊戲帳號");
		$param = [
			'action' => 'createuser',
			'channel' => self::HQT_CHANNEL,
			'password' => $this->genPassword(),
			'username' => $this->genAccount(),
		];

		/* 簽名 */
		$sign = $this->makeSign($param);
		$param['sign'] = $sign;

		$apiUrl = self::HQT_HOST . "?" . http_build_query($param);
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
			'channel' 	=> self::HQT_CHANNEL,
			'gamecode' 	=> '',
			'loadbg' 	=> '',
			'password' 	=> $this->password,
			'username' 	=> $this->username,
			'tableid' 	=> '',
		];

		/* 簽名 */
		$sign = $this->makeSign($param);
		$param['sign'] = $sign;

		$apiUrl = self::HQT_HOST . "?" . http_build_query($param);
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

		/* 更新log時間 */
		$this->userAccRepository->updateAcc(['uid' => auth()->id(), 'platform' => 'HQT'],['response' => $apiResponse['errcode']]);

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
			'channel' 	=> self::HQT_CHANNEL,
			'password' 	=> $this->password,
			'username' 	=> $this->username,
			'amount'	=> $amount,
			'orderno'	=> $this->genOrdNo(),
		];

		/* 簽名 */
		$sign = $this->makeSign($param);
		$param['sign'] = $sign;

		$apiUrl = self::HQT_HOST . "?" . http_build_query($param);
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

		/* 更新log時間 */
		$this->userAccRepository->updateAcc(['uid' => auth()->id(), 'platform' => 'HQT'],['response' => $apiResponse['errcode']]);

		return $apiResponse;
	}
}
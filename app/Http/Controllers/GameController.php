<?php
/**
 * 遊戲 控制器
 * @author Weine
 * @date 2019/11/12
 * @apiDefine Game 遊戲管理
 */
namespace App\Http\Controllers;

use App\Services\GameService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class GameController extends Controller
{
	protected $gameService;

	public function __construct(
		Request $request,
		GameService $gameService
	){
		parent::__construct($request);

		$this->gameService = $gameService;
	}

	/**
	 * @api {get} /game/entry 遊戲接入口
	 * @apiDescription mobile版URL前綴: /api/m
	 *
	 * pc版URL前綴: /api
	 * @apiGroup Game
	 * @apiName HQT遊戲平台
	 * @apiVersion 1.0.0
	 *
	 * @apiHeader (Mobile Header) {String} Authorization Mobile 須帶入 JWT Token
	 * @apiHeader (Web Header) {String} Cookie Web 須帶入登入後的 SESSID
	 *
	 * @apiError (Error Status) 102 執行遊戲失敗
	 * @apiError (Error Status) 103 設置狀態為關閉
	 * @apiError (Error Status) 999 API執行錯誤
	 *
	 * @apiSuccess {String} game_url 遊戲網址
	 *
	 * @apiSuccessExample 成功回應
	 * {
	"status": 1,
	"msg": "成功",
	"data": {
	"game_url": "http:\/\/localhost\/index.html?uid=52280&loadbg=aaa&token=TOKEN_03b6aa3335fa29bdc091ec4cc6d34511"
	}
	}
	 */
	public function entry()
	{
		try {
			if(!$this->gameService->checkSetting()) {
				info('設置狀態為關閉');
				$this->setStatus(103, '服务器连接失败');
				return $this->jsonOutput();
			}

			$result = $this->gameService->login();
			if(empty($result)) {
				Log::error('執行遊戲失敗');
				$this->setStatus(102, '服务器连接失败');
				return $this->jsonOutput();
			}

			$this->setStatus(1, '成功');
			$this->setData('game_url', $result['result']);
			return $this->jsonOutput();

		} catch (\Exception $e) {
			Log::error($e->getMessage());
			$this->setStatus(999, '服务器连接失败');
			return $this->jsonOutput();
		}
	}

	/**
	 * @api {get} /game/deposit 儲值
	 * @apiDescription mobile版URL前綴: /api/m
	 *
	 * pc版URL前綴: /api
	 * @apiGroup Game
	 * @apiName deposit
	 * @apiVersion 1.0.0
	 *
	 * @apiHeader (Mobile Header) {String} Authorization Mobile 須帶入 JWT Token
	 * @apiHeader (Web Header) {String} Cookie Web 須帶入登入後的 SESSID
	 *
	 * @apiError (Error Status) 102 執行遊戲失敗
	 * @apiError (Error Status) 103 設置狀態為關閉
	 * @apiError (Error Status) 104 無儲值金額
	 * @apiError (Error Status) 999 API執行錯誤
	 *
	 * @apiSuccessExample 成功回應
	 * {
	"status": 1,
	"msg": "成功",
	"data": []
	}
	 */
	public function deposit(Request $request)
	{
		try {
			if(!$this->gameService->checkSetting()) {
				$this->setStatus(103, '設置狀態為關閉');
				return $this->jsonOutput();
			}

			if(!$request->get('amount')) {
				$this->setStatus(104, '無儲值金額');
				return $this->jsonOutput();
			}

			$result = $this->gameService->deposit($request->amount);
			if(empty($result)) {
				$this->setStatus(102, '儲值失敗');
				return $this->jsonOutput();
			}

			$this->setStatus(1, '成功');
			return $this->jsonOutput();

		} catch (\Exception $e) {
			Log::error($e->getMessage());
			$this->setStatus(999, 'API執行錯誤');
			return $this->jsonOutput();
		}
	}
}

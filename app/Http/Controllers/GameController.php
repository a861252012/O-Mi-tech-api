<?php
/**
 * 遊戲 控制器
 * @author Weine
 * @date 2019/11/12
 * @apiDefine Game 遊戲管理
 */
namespace App\Http\Controllers;

use App\Http\Requests\Game\GameEntry;
use App\Services\GameListService;
use App\Services\GameService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class GameController extends Controller
{
	protected $gameService;
	protected $gameListService;

	public function __construct(
		Request $request,
		GameService $gameService,
        GameListService $gameListService
	){
		parent::__construct($request);

		$this->gameService = $gameService;
		$this->gameListService = $gameListService;
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
     * @apiParam {String} gp_id 遊戲商ID
     * @apiParam {String} game_code 遊戲代碼
	 *
     * @apiError (Error Status) 0 輸入參數相關錯誤
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
	public function entry(GameEntry $request)
	{
		try {
			if(!$this->gameService->checkSetting()) {
				info('設置狀態為關閉');
				$this->setStatus(103, __('messages.Game.entry.connect_failed'));
				return $this->jsonOutput();
			}

			$result = $this->gameService->login($request->game_code);
			if(empty($result)) {
				Log::error('執行遊戲失敗');
				$this->setStatus(102, __('messages.Game.entry.connect_failed'));
				return $this->jsonOutput();
			}

			$this->setStatus(1, __('messages.success'));
			$this->setData('game_url', $result['result']);
			return $this->jsonOutput();

		} catch (\Exception $e) {
			Log::error($e->getMessage());
			$this->setStatus(999, __('messages.Game.entry.connect_failed'));
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
				$this->setStatus(103, __('messages.Game.deposit.status_down'));
				return $this->jsonOutput();
			}

			if(!$request->get('amount')) {
				$this->setStatus(104, __('messages.Game.deposit.amount_required'));
				return $this->jsonOutput();
			}

			$result = $this->gameService->deposit($request->amount);
			if(empty($result)) {
				$this->setStatus(102, __('messages.Game.deposit.failed'));
				return $this->jsonOutput();
			}

			$this->setStatus(1, __('messages.success'));
			return $this->jsonOutput();

		} catch (\Exception $e) {
			Log::error($e->getMessage());
			$this->setStatus(999, __('messages.apiError'));
			return $this->jsonOutput();
		}
	}

    /**
     * @api {get} /game/game_list 遊戲列表
     * @apiDescription mobile版URL前綴: /api/m
     *
     * pc版URL前綴: /api
     * @apiGroup Game
     * @apiName GameList
     * @apiVersion 1.0.0
     *
     *
     * @apiHeader (Mobile Header) {String} Authorization Mobile 須帶入 JWT Token
     * @apiHeader (Web Header) {String} Cookie Web 須帶入登入後的 SESSID
     *
     * @apiError (Error Status) 201 小遊戲目前維修中
     * @apiError (Error Status) 999 API執行錯誤
     *
     * @apiSuccess {Array} game_list
     * @apiSuccess {Int} id 流水號
     * @apiSuccess {String} gp_id 遊戲商ID
     * @apiSuccess {String} game_code 遊戲代碼
     * @apiSuccess {String} game_name 遊戲名稱
     * @apiSuccess {String} game_icon 圖示
     * @apiSuccess {Int} sort 排序
     *
     * @apiSuccessExample 成功回應
     * {
    "status": 1,
    "msg": "OK",
    "data": {
    "game_list": [
    {
    "id": 27,
    "gp_id": "GPHQT",
    "game_code": "bcbm",
    "game_name": "奔驰宝马",
    "game_icon": "http:\/\/10.2.121.240:9869\/43975bf54ad7b93c6b84a5ac8a42341a.jpg",
    "sort": 14
    },
    {
    "id": 21,
    "gp_id": "GPHQT",
    "game_code": "ebg",
    "game_name": "二八杠",
    "game_icon": "http:\/\/10.2.121.240:9869\/43975bf54ad7b93c6b84a5ac8a42341a.jpg",
    "sort": 13
    },
    {
    "id": 16,
    "gp_id": "GPHQT",
    "game_code": "qznn",
    "game_name": "抢庄牛牛",
    "game_icon": "http:\/\/10.2.121.240:9869\/43975bf54ad7b93c6b84a5ac8a42341a.jpg",
    "sort": 12
    }
    ]
    }
    }
     */
	public function gameList()
    {
        try {
            if(!$this->gameService->checkSetting()) {
                info('設置狀態為關閉');
                $this->setStatus(201, __('messages.Game.gameList.maintained'));
                return $this->jsonOutput();
            }

            $result = $this->gameListService->getList();
            if ($result->isEmpty()) {
                $this->setStatus(201, __('messages.Game.gameList.maintained'));
                return $this->jsonOutput();
            }

            $this->setStatus(1, __('messages.success'));
            $this->setData('game_list', $result);
            return $this->jsonOutput();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            $this->setStatus(999, __('messages.apiError'));
            return $this->jsonOutput();
        }
    }
}

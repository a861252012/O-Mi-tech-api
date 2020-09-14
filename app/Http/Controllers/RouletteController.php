<?php
/**
 * 輪盤遊戲 控制器
 * @author Weine
 * @date 2020-9-11
 * @apiDefine Roulette 輪盤遊戲
 */
namespace App\Http\Controllers;

use App\Http\Requests\Game\GetRouletteHistory;
use App\Services\Roulette\RouletteService;
use Illuminate\Support\Facades\Auth;

class RouletteController extends Controller
{
    protected $rouletteService;

    public function __construct(RouletteService $rouletteService)
    {
        $this->rouletteService = $rouletteService;
    }

    /**
     * @api {get} /roulette/setting 取得配置
     *
     * @apiDescription mobile版URL前綴: /api/m
     *
     * pc版URL前綴: /api
     * @apiGroup Roulette
     * @apiName 取得配置
     * @apiVersion 1.0.0
     *
     * @apiHeader (Mobile Header) {String} Authorization Mobile 須帶入 JWT Token
     * @apiHeader (Web Header) {String} Cookie Web 須帶入登入後的 SESSID
     *
     * @apiError (Error Status) 0 輪盤遊戲未開啟
     * @apiError (Error Status) 999 API執行錯誤
     *
     * @apiSuccess {Int} switch 是否啟用(0:否/1:是)
     * @apiSuccess {Int} cost 單次價格
     * @apiSuccess {Array} items
     * @apiSuccess {Int} items.type 獎品類型：
     * 1: 鑽<br>
    2: 經驗值<br>
    3: vip1 體驗卷<br>
    4: vip2 體驗卷<br>
    5: vip3 體驗卷<br>
    6: vip4 體驗卷<br>
    7: vip5 體驗卷<br>
    8: vip6 體驗卷<br>
    9: vip7 體驗卷<br>
    10: 神秘大獎
     * @apiSuccess {Int} items.amount 數量
     * @apiSuccess {Int} free 免費次數
     * @apiSuccess {Int} points 用戶鑽石餘額
     *
     * @apiSuccessExample 成功回應
     *{
    "status": 1,
    "msg": "Successful",
    "data": {
    "cost": 10,
    "items": [
    {
    "type": 2,
    "amount": 500
    },
    {
    "type": 1,
    "amount": 9999
    },
    {
    "type": 1,
    "amount": 3000
    },
    {
    "type": 1,
    "amount": 1000
    },
    {
    "type": 6,
    "amount": 1
    },
    {
    "type": 1,
    "amount": 500
    },
    {
    "type": 1,
    "amount": 1000
    },
    {
    "type": 1,
    "amount": 1
    }
    ],
    "free": 0,
    "points": "2068"
    }
    }
     */
    public function setting()
    {
        try {
            if (!$this->rouletteService->status()) {
                $this->setStatus(0, 'messages.Roulette.setting.is_off');
                return $this->jsonOutput();
            }

            $this->setStatus(1, __('messages.success'));
            $this->setData('cost', $this->rouletteService->cost());
            $this->setData('items', $this->rouletteService->items());
            $this->setData('free', $this->rouletteService->freeTicket());
            $this->setData('points', (int)Auth::user()->points);

            return $this->jsonOutput();
        } catch (\Exception $e) {
            report($e);
            $this->setStatus(999, __('messages.apiError'));
            return $this->jsonOutput();
        }
    }

    /**
     * @api {post} /roulette/history 取得用戶中獎紀錄列表
     * @apiGroup roulette
     * @apiName 取得用戶中獎紀錄列表
     * @apiVersion 1.0.0
     *
     * @apiHeader (Mobile Header) {String} Authorization Mobile 須帶入 JWT Token
     * @apiHeader (Web Header) {String} Cookie Web 須帶入登入後的 SESSID
     *
     * @apiParam {String} startTime 起日 (手機端為必填參數)
     * @apiParam {String} endTime 迄日 (手機端為必填參數)
     * @apiParam {int} page 第幾頁 (不帶則預設第一頁)
     * @apiParam {int} amount 一頁顯示幾筆 (必填參數 手機:15筆,PC:100筆)
     *
     * @apiError (Error Status) 999 API執行錯誤
     *
     * @apiSuccess {Int} status 執行狀態(1為執行成功,1以外為執行失敗)
     * @apiSuccess {String} msg 執行結果敘述
     *
     * @apiSuccess {Int} id 流水號
     * @apiSuccess {Int} uid 用戶uid
     * @apiSuccess {Int} type 中獎道具種類
     * @apiSuccess {Int} amount 中獎道具數量
     * @apiSuccess {Int} rid 主播id
     * @apiSuccess {Int} is_free 是否為免費次數（0:不是/1:是）
     * @apiSuccess {Date} created_at 創建時間
     *
     * @apiSuccessExample {json} 成功回應
     * "status": 1,
     * "msg": "成功",
     * "data": {
     * "roulette_history": [
     * {
     * "id": 1,
     * "uid": 9493318,
     * "type": 1,
     * "amount": 1,
     * "rid": 0,
     * "is_free": 1,
     * "created_at": "2020-09-11 09:29:26"
     * }
     * ]
     * }
     * },
     * "first_page_url": "http:\/\/localhost\/api\/m\/roulette\/history?page=1",
     * "from": 1,
     * "last_page": 3,
     * "last_page_url": "http:\/\/localhost\/api\/m\/roulette\/history?page=3",
     * "next_page_url": "http:\/\/localhost\/api\/m\/roulette\/history?page=2",
     * "path": "http:\/\/localhost\/api\/m\/roulette\/history",
     * "per_page": "15",
     * "prev_page_url": null,
     * "to": 15,
     * "total": 39
     */
    public function getHistory(GetRouletteHistory $request)
    {
        try {
            $data = $this->rouletteService->getHistory(
                Auth::id(),
                $request->amount,
                $request->startTime,
                $request->endTime
            );

            $this->setStatus(1, __('messages.success'));
            $this->setData('roulette_history', $data);
            return $this->jsonOutput();
        } catch (\Exception $e) {
            report($e);
            $this->setStatus(999, __('messages.apiError'));
            return $this->jsonOutput();
        }
    }

}

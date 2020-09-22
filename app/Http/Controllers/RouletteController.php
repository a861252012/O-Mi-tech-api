<?php
/**
 * 輪盤遊戲 控制器
 * @author Weine
 * @date 2020-9-11
 * @apiDefine Roulette 輪盤遊戲
 */

namespace App\Http\Controllers;

use App\Http\Requests\Roulette\RouletteGetHistory;
use App\Http\Requests\Roulette\RoulettePlay;
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
     * @apiName setting
     * @apiVersion 1.0.0
     *
     * @apiHeader (Mobile Header) {String} Authorization Mobile 須帶入 JWT Token
     * @apiHeader (Web Header) {String} Cookie Web 須帶入登入後的 SESSID
     *
     * @apiError (Error Status) 0 輪盤遊戲未開啟
     * @apiError (Error Status) 999 API執行錯誤
     *
     * @apiSuccess {Int} cost 單次價格
     * @apiSuccess {Array} items
     * @apiSuccess {Int} items.type 獎品類型：
     * 1: 鑽<br>
     * 2: 經驗值<br>
     * 3: vip1 體驗卷<br>
     * 4: vip2 體驗卷<br>
     * 5: vip3 體驗卷<br>
     * 6: vip4 體驗卷<br>
     * 7: vip5 體驗卷<br>
     * 8: vip6 體驗卷<br>
     * 9: vip7 體驗卷<br>
     * 10: 神秘大獎
     * @apiSuccess {Int} items.amount 數量
     * @apiSuccess {String} items.icon 獎品圖示
     * @apiSuccess {Int} free 免費次數
     * @apiSuccess {Int} points 用戶鑽石餘額
     *
     * @apiSuccessExample {json} 成功回應
     *{
    "status": 1,
    "msg": "Successful",
    "data": {
    "cost": 10,
    "items": [
    {
    "type": 2,
    "amount": 500,
    "icon": "http:\/\/zimg:4869\/3a9a06c1b7e961f1a917675c1b94d000.png"
    },
    {
    "type": 10,
    "amount": 1,
    "icon": "http:\/\/zimg:4869\/6e91c040a26b1139122e82d3169604ed.png"
    },
    {
    "type": 1,
    "amount": 9999,
    "icon": "http:\/\/zimg:4869\/91a838c0ccff821e322c71dbb5fe74cf.png"
    },
    {
    "type": 3,
    "amount": 1,
    "icon": "http:\/\/zimg:4869\/97dfcc3c9f508d18318c7f79cca3b7e2.png"
    },
    {
    "type": 6,
    "amount": 1,
    "icon": "http:\/\/zimg:4869\/a505da38bfd9a67e9f2b5ce33f3a86cc.png"
    },
    {
    "type": 4,
    "amount": 1,
    "icon": "http:\/\/zimg:4869\/07af4cbeec9ae21bc1d27ea1e7dfe1a1.png"
    },
    {
    "type": 5,
    "amount": 1,
    "icon": "http:\/\/zimg:4869\/9cc8e9cf6c6f82e63fe225982b1ed621.png"
    },
    {
    "type": 1,
    "amount": 1,
    "icon": "http:\/\/zimg:4869\/91a838c0ccff821e322c71dbb5fe74cf.png"
    }
    ],
    "free": 0,
    "points": 91282
    }
    }
     */
    public function setting()
    {
        try {
            if (!$this->rouletteService->status()) {
                $this->setStatus(0, __('messages.Roulette.setting.is_off'));
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
     *
     * @apiDescription mobile版URL前綴: /api/m
     *
     * pc版URL前綴: /api
     * @apiGroup Roulette
     * @apiName history
     * @apiVersion 1.0.0
     *
     * @apiHeader (Mobile Header) {String} Authorization Mobile 須帶入 JWT Token
     * @apiHeader (Web Header) {String} Cookie Web 須帶入登入後的 SESSID
     *
     * @apiParam {String} [startTime] 起日 (手機端為必填參數,預設為一週前)
     * @apiParam {String} [endTime] 迄日  (手機端為必填參數,預設為當前時間)
     * @apiParam {int} [page] 第幾頁 (不帶則預設第一頁)
     * @apiParam {int} amount 一頁顯示幾筆 (必填參數 手機:15筆,PC:100筆)
     *
     * @apiParamExample {json} Request-Example:
     * {
    "startTime":"2020-9-18",
    "endTime":"2020-9-18",
    "amount":15
    }
     *
     * @apiError (Error Status) 999 API執行錯誤
     *
     * @apiSuccess {Array} data 紀錄
     * @apiSuccess {Int} data.current_page 目前頁碼
     *
     * @apiSuccess {Array} data.data
     * @apiSuccess {Int} data.data.id 流水號
     * @apiSuccess {Int} data.data.uid 用戶uid
     * @apiSuccess {Int} data.data.type 中獎道具種類
     * @apiSuccess {Int} data.data.amount 中獎道具數量
     * @apiSuccess {Int} data.data.rid 主播id
     * @apiSuccess {Int} data.data.cost 花費鑽石
     * @apiSuccess {Int} data.data.is_free 是否為免費次數（0:不是/1:是）
     * @apiSuccess {String} data.data.created_at 創建時間
     * @apiSuccess {String} data.data.name 獎項名稱
     *
     * @apiSuccess {Int} data.first_page_url 第一頁url
     * @apiSuccess {Int} data.from 從第幾頁換頁
     * @apiSuccess {Int} data.last_page 最後一頁頁碼
     * @apiSuccess {Int} data.last_page_url 最後一頁url
     * @apiSuccess {Int} data.next_page_url 下一頁url
     * @apiSuccess {Int} data.path
     * @apiSuccess {Int} data.per_page 筆數
     * @apiSuccess {Int} data.prev_page_url 前一頁url
     * @apiSuccess {Int} data.to 跳轉到到第幾頁
     * @apiSuccess {Int} data.total 總頁數
     *
     * @apiSuccessExample {json} 成功回應
     * {
    "status": 1,
    "msg": "Successful",
    "data": {
    "current_page": 1,
    "data": [
    {
    "id": 2,
    "uid": 9493580,
    "type": 2,
    "amount": 500,
    "rid": 9491922,
    "cost": 0,
    "is_free": 1,
    "group_id": 1600413711465977,
    "created_at": "2020-09-18 15:21:51",
    "name": "经验值"
    },
    {
    "id": 1,
    "uid": 9493580,
    "type": 1,
    "amount": 1,
    "rid": 9491922,
    "cost": 0,
    "is_free": 1,
    "group_id": 1600413601920142,
    "created_at": "2020-09-18 15:20:01",
    "name": "钻石"
    }
    ],
    "first_page_url": "http:\/\/localhost\/api\/m\/roulette\/history?page=1",
    "from": 1,
    "last_page": 1,
    "last_page_url": "http:\/\/localhost\/api\/m\/roulette\/history?page=1",
    "next_page_url": null,
    "path": "http:\/\/localhost\/api\/m\/roulette\/history",
    "per_page": 15,
    "prev_page_url": null,
    "to": 2,
    "total": 2
    }
    }
     */
    public function getHistory(RouletteGetHistory $request)
    {
        try {
            $data = $this->rouletteService->getHistory(
                Auth::id(),
                (int)$request->amount,
                $request->startTime,
                $request->endTime
            );

            $this->setStatus(1, __('messages.success'));
            $this->setRootData('data', $data);
            return $this->jsonOutput();
        } catch (\Exception $e) {
            report($e);
            $this->setStatus(999, __('messages.apiError'));
            return $this->jsonOutput();
        }
    }

    /**
     * @api {post} /roulette/play 抽獎
     *
     * @apiDescription mobile版URL前綴: /api/m
     *
     * pc版URL前綴: /api
     * @apiGroup Roulette
     * @apiName play
     * @apiVersion 1.0.0
     *
     * @apiHeader (Mobile Header) {String} Authorization Mobile 須帶入 JWT Token
     * @apiHeader (Web Header) {String} Cookie Web 須帶入登入後的 SESSID
     *
     * @apiParam {Int} rid 房間ID
     * @apiParam {Int} [cnt] 抽獎次數(預設為1次)
     *
     * @apiParamExample {json} Request-Example:
     * {
    "rid":9491922,
    "cnt":10
    }
     *
     * @apiError (Error Status) 0 次数输入错误
     * @apiError (Error Status) 102 免费票或钻石不足
     * @apiError (Error Status) 999 API執行錯誤
     *
     * @apiSuccess {Array} reward 獎項
     * @apiSuccess {Int} reward.type 類型
     * @apiSuccess {Int} reward.amount 數量
     * @apiSuccess {Int} reward.broadcast 是否上跑道(0:否/1:是)
     * @apiSuccess {Int} roulette_switch 輪盤遊戲開關(0:關/1:開)
     * @apiSuccess {Int} cost 單一次消費
     * @apiSuccess {Int} free 免費次數
     * @apiSuccess {Int} points 用戶鑽石餘額
     *
     * @apiSuccessExample {json} 成功回應
     *{
    "status": 1,
    "msg": "Successful",
    "data": {
    "reward": [
    {
    "type": 2,
    "amount": 500,
    "broadcast": 1
    },
    {
    "type": 1,
    "amount": 1,
    "broadcast": 0
    }
    ],
    "roulette_switch": 1,
    "cost": 10,
    "free": 0,
    "points": 1938
    }
    }
     */
    public function play(RoulettePlay $request)
    {
        try {
            $cnt = (int)$request->cnt;
            if (empty($cnt)) {
                $cnt = 1;
            }

            $rid = (int)$request->rid;

            //檢查鑽石或免費票是否足夠
            if ($this->rouletteService->checkPlay($cnt)) {
                $this->setStatus(102, __('messages.Roulette.play.not_enough_free_or_points'));
                return $this->jsonOutput();
            }

            $result = $this->rouletteService->play($rid, $cnt);
            if (false === $result) {
                $this->setStatus(0, __('messages.Roulette.play.failed'));
                return $this->jsonOutput();
            }

            $this->setStatus(1, __('messages.success'));
            $this->setData('reward', $result);
            $this->setData('roulette_switch', $this->rouletteService->status());
            $this->setData('cost', $this->rouletteService->cost());
            $this->setData('free', $this->rouletteService->freeTicket());
            $this->setData('points', (int)Auth::user()->refresh()->points);
            return $this->jsonOutput();
        } catch (\Exception $e) {
            report($e);
            $this->setStatus(999, __('messages.apiError'));
            return $this->jsonOutput();
        }
    }

    /**
     * @api {get} /roulette/marquee 輪盤跑道
     *
     * @apiDescription mobile版URL前綴: /api/m
     *
     * pc版URL前綴: /api
     * @apiGroup Roulette
     * @apiName marquee
     * @apiVersion 1.0.0
     *
     * @apiHeader (Mobile Header) {String} Authorization Mobile 須帶入 JWT Token
     * @apiHeader (Web Header) {String} Cookie Web 須帶入登入後的 SESSID
     *
     * @apiError (Error Status) 999 API執行錯誤
     *
     * @apiSuccess {Array} data 前十跑道用戶列表
     * @apiSuccess {Int} data.uid 用戶uid
     * @apiSuccess {Int} data.nickname 用戶暱稱
     * @apiSuccess {Int} data.type 中獎道具類型
     * @apiSuccess {Int} data.amount 中獎道具數量
     *
     * @apiSuccessExample {json} 成功回應
     *{
    "status": 1,
    "msg": "Successful",
    "data": [
    {
    "uid": 9493580,
    "nickname": "weinet90",
    "type": 6,
    "amount": 1
    },
    {
    "uid": 9493580,
    "nickname": "weinet90",
    "type": 3,
    "amount": 1
    }
    ]
    }
     */
    public function marquee()
    {
        try {
            $data = $this->rouletteService->getLastTenInfo();
            $this->setStatus(1, __('messages.success'));
            $this->setRootData('data', $data);
            return $this->jsonOutput();
        } catch (\Exception $e) {
            report($e);
            $this->setStatus(999, __('messages.apiError'));
            return $this->jsonOutput();
        }
    }
}

<?php
/**
 * 輪盤遊戲 控制器
 * @author Weine
 * @date 2020-9-11
 * @apiDefine Roulette 輪盤遊戲
 */
namespace App\Http\Controllers;

use App\Services\RouletteService;
use Illuminate\Http\Request;

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
     * @apiError (Error Status) 1 成功
     * @apiError (Error Status) 999 API執行錯誤
     *
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
     *
     * @apiSuccessExample 成功回應
     *{
    "status": 1,
    "msg": "成功",
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
    ]
    }
    }
     */
    public function setting()
    {
        try {
            $setting = $this->rouletteService->getSetting();

            $this->setStatus(1, __('messages.success'));
            $this->setRootData('data', $setting);
            return $this->jsonOutput();
        } catch (\Exception $e) {
            report($e);
            $this->setStatus(999, __('messages.apiError'));
            return $this->jsonOutput();
        }
    }
}

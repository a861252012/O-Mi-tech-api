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
     * @apiSuccess {Int} type 獎品類型
     * (1: 鑽
    2: 經驗值
    3: vip1 體驗卷
    4: vip2 體驗卷
    5: vip3 體驗卷
    6: vip4 體驗卷
    7: vip5 體驗卷
    8: vip6 體驗卷
    9: vip7 體驗卷
    10: 神秘大獎
    )
     * @apiSuccess {Int} amount 數量
     * @apiSuccess {Int} rate 中獎機率
     * @apiSuccess {Int} broadcast 是否有跑馬燈(0:否 / 1:是)
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
    "amount": 500,
    "rate": 1,
    "broadcast": 1
    },
    {
    "type": 1,
    "amount": 9999,
    "rate": 0.01,
    "broadcast": 1
    },
    {
    "type": 1,
    "amount": 3000,
    "rate": 0.03,
    "broadcast": 1
    },
    {
    "type": 1,
    "amount": 1000,
    "rate": 0.05,
    "broadcast": 1
    },
    {
    "type": 6,
    "amount": 1,
    "rate": 1,
    "broadcast": 1
    },
    {
    "type": 1,
    "amount": 500,
    "rate": 0.14,
    "broadcast": 0
    },
    {
    "type": 1,
    "amount": 1000,
    "rate": 0.05,
    "broadcast": 1
    },
    {
    "type": 1,
    "amount": 1,
    "rate": 94.77,
    "broadcast": 0
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

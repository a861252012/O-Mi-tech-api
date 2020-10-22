<?php
/**
 * 通知提醒 控制器
 * @author Weine
 * @date 2020-10-19
 * @apiDefine Notification 通知提醒
 */

namespace App\Http\Controllers;

use App\Services\Notification\RoomOneToMoreService;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    protected $roomOneToMoreService;
    
    public function __construct(RoomOneToMoreService $roomOneToMoreService)
    {
        $this->roomOneToMoreService = $roomOneToMoreService;
    }

    /**
     * @api {get} /notification/show 主播開車提醒列表
     * @apiDescription mobile版URL前綴: /api/m
     *
     * pc版URL前綴: /api
     * @apiGroup Notification
     * @apiName show
     * @apiVersion 1.0.0
     *
     *
     * @apiHeader (Mobile Header) {String} Authorization Mobile 須帶入 JWT Token
     * @apiHeader (Web Header) {String} Cookie Web 須帶入登入後的 SESSID
     *
     * @apiError (Error Status) 999 API執行錯誤
     * 
     * @apiSuccess {Array} data
     * @apiSuccess {Int} uid 主播uid
     * @apiSuccess {String} nickname 主播暱稱
     * @apiSuccess {Int} price 門票價格
     * @apiSuccess {String} cover 主播海報
     * @apiSuccess {Int} time 剩餘時間(秒)
     * 
     * @apiSuccessExample 成功回應
     * {
    "status": 1,
    "msg": "成功",
    "data": [
    {
    "uid": 9492034,
    "nickname": "Isaac3",
    "price": 399,
    "cover": "9492034_1573453712.jpg",
    "time": 60
    },
    {
    "uid": 9491968,
    "nickname": "艾薩科超級長的暱稱補滿",
    "price": 399,
    "cover": "9491968_1588682045.jpeg",
    "time": 30
    },
    {
    "uid": 9491878,
    "nickname": "Ace",
    "price": 399,
    "cover": "9491878_1571217104.jpg",
    "time": 10
    }
    ]
    }
     */
    public function show()
    {
        try {
//            $result = $this->roomOneToMoreService->getShowInFiveMins();
            
            $result = array();
            $result[] = [
                'uid'      => 9492034,
                'nickname' => 'Isaac3',
                'price'    => 399,
                'cover'    => '9492034_1573453712.jpg',
                'time'     => 60,
            ];

            $result[] = [
                'uid'      => 9491968,
                'nickname' => '艾薩科超級長的暱稱補滿',
                'price'    => 399,
                'cover'    => '9491968_1588682045.jpeg',
                'time'     => 30,
            ];

            $result[] = [
                'uid'      => 9491878,
                'nickname' => 'Ace',
                'price'    => 399,
                'cover'    => '9491878_1571217104.jpg',
                'time'     => 10,
            ];
            
            $this->setStatus(1, __('messages.success'));
            $this->setRootData('data', $result);
            return $this->jsonOutput();
            
        } catch (\Exception $e) {
            report($e);
            $this->setStatus(999, __('messages.apiError'));
            return $this->jsonOutput();
        }
    }
}

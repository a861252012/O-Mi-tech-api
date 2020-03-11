<?php
/**
 * Socket 線路 控制器
 * @author Weine
 * @date 2020-03-10
 * @apiDefine Socket socket
 */
namespace App\Http\Controllers;

use App\Facades\SiteSer;
use App\Services\SocketProxyService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class SocketController extends Controller
{
    protected $socketProxyService;

    public function __construct(SocketProxyService $socketProxyService)
    {
        $this->socketProxyService = $socketProxyService;
    }

    /**
     * @api {get} /socket/proxy_list 取得線路列表
     * @apiGroup Socket
     * @apiName proxy_list
     * @apiVersion 1.0.0
     *
     * @apiError (Error Status) 999 API執行錯誤
     *
     * @apiSuccess {Array[]} proxy_list 線路列表
     * @apiSuccess {String} name 線路名稱
     * @apiSuccess {String} host 主機位址
     *
     * @apiSuccess {String} socket_desc 直播間說明
     *
     * @apiSuccessExample {json} 成功回應
     *{
    "status": "1",
    "msg": "OK",
    "data": {
    "proxy_list": [
    {
    "name": "紅",
    "host": "192.168.0.1"
    },
    {
    "name": "橙",
    "host": "192.168.0.2"
    }
    ],
    "socket_desc": "test"
    }
    }
     */
    public function proxyList()
    {
        try {
            $result = $this->socketProxyService->proxyList();
            $socketDesc = SiteSer::globalSiteConfig('socket_desc');

            $this->setStatus('1', 'OK');
            $this->setData('proxy_list', $result);
            $this->setData('socket_desc', $socketDesc);
            return $this->jsonOutput();
        } catch (\Exception $e) {
            report($e);
            $this->setStatus('999', 'API執行錯誤');
            return $this->jsonOutput();
        }
    }
}

<?php
/**
 * Socket 線路 控制器
 * @author Weine
 * @date 2020-03-10
 * @apiDefine Socket socket
 */
namespace App\Http\Controllers\v2;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class SocketController extends Controller
{
    public function __construct()
    {
    }

    /**
     * @api {get} /socket/channel_list 取得線路列表
     * @apiGroup Socket
     * @apiName channel_list
     * @apiVersion 2.0.0
     *
     * @apiError (Error Status) 999 API執行錯誤
     *
     * @apiSuccess {String} name 線路名稱
     * @apiSuccess {String} host 主機位址
     *
     * @apiSuccessExample {json} 成功回應
     *{
    "status": "1",
    "msg": "OK",
    "data": [
    {
    "name": "紅",
    "host": "192.168.0.1"
    },
    {
    "name": "橙",
    "host": "192.168.0.2"
    },
    {
    "name": "黃",
    "host": "192.168.0.3"
    },
    {
    "name": "綠",
    "host": "192.168.0.4"
    },
    {
    "name": "藍",
    "host": "192.168.0.5"
    },
    {
    "name": "粉紅",
    "host": "192.168.0.6"
    },
    {
    "name": "紫",
    "host": "192.168.0.7"
    },
    {
    "name": "橘",
    "host": "192.168.0.8"
    }
    ]
    }
     */
    public function channelList()
    {
        try {
            $data = [
                ['name' => '紅', 'host' => '192.168.0.1'],
                ['name' => '橙', 'host' => '192.168.0.2'],
                ['name' => '黃', 'host' => '192.168.0.3'],
                ['name' => '綠', 'host' => '192.168.0.4'],
                ['name' => '藍', 'host' => '192.168.0.5'],
                ['name' => '粉紅', 'host' => '192.168.0.6'],
                ['name' => '紫', 'host' => '192.168.0.7'],
                ['name' => '橘', 'host' => '192.168.0.8'],
            ];

            $this->setStatus('1', 'OK');
            $this->setRootData('data', $data);
            return $this->jsonOutput();
        } catch (\Exception $e) {
            report($e);
            $this->setStatus('999', 'API執行錯誤');
            return $this->jsonOutput();
        }
    }
}

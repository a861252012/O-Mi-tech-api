<?php

namespace App\Http\Controllers;

use App\Facades\SiteSer;
use App\Services\Sms\SmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class SmsController extends Controller
{
    public function send(Request $req)
    {
        $cc = $req->post('cc');
        $mobile = $req->post('mobile');
        $act = $req->post('act');

        // input validation
        if (empty($cc) || empty($mobile) || empty($act)) {
            return $this->msg('Invalid request');
        }

        // check reg mobile exists
        if ($act == SmsService::ACT_REG) {
            $site_id = SiteSer::siteId();
            $redis = resolve('redis');
            $cc_mobile = $cc.$mobile;
            if ($redis->hExists('hcc_mobile_to_id:' . $site_id, $cc_mobile)) {
                return $this->msg('对不起, 该手机号已被使用!');
            }
        }

        $result = SmsService::send($act, $cc, $mobile);
        if ($result !== true) {
            return $this->msg($result);
        }
        return $this->msg('成功发送', 1);
    }
}

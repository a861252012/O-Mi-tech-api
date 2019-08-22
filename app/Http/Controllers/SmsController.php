<?php

namespace App\Http\Controllers;

use App\Services\Sms\SmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SmsController extends Controller
{
    public function send(Request $req)
    {
        $cc = $req->post('cc');
        $mobile = $req->post('mobile');
        $act = $req->post('act');
        $resp = [
            'status' => 1,
            'msg' => '成功发送',
        ];

        // input validation
        if (empty($cc) || empty($mobile) || empty($act)) {
            $resp['status'] = 0;
            $resp['msg'] = 'Invalid request';
            return JsonResponse::create($resp);
        }

        // TODO: check reg mobile exists

        $result = SmsService::send($act, $cc, $mobile);
        if ($result !== true) {
            $resp['status'] = 0;
            $resp['msg'] = $result;
        }
        return JsonResponse::create($resp);
    }
}

<?php

namespace App\Http\Controllers;

use App\Facades\SiteSer;
use App\Services\I18n\PhoneNumber;
use App\Services\Sms\SmsService;
use GuzzleHttp\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class SmsController extends Controller
{
    const API_KEY = '9e4a9df8a3b3ab0ef5f1552f1ab66ab8';

    public function send(Request $req)
    {
        $cc = $req->post('cc');
        $mobile = $req->post('mobile');
        $act = $req->post('act');

        // input validation
        if (empty($cc) || empty($mobile) || empty($act)) {
            return $this->msg('Invalid request');
        }
        $mobile = PhoneNumber::formatMobile($cc, $mobile);

        // check reg mobile exists
        $cc_mobile = $cc.$mobile;
        $site_id = SiteSer::siteId();
        $redis = resolve('redis');
        $exists = $redis->hExists('hcc_mobile_to_id:' . $site_id, $cc_mobile);

        if ($act == SmsService::ACT_REG && $exists) {
            return $this->msg('对不起, 该手机号已被使用!');
        } else if ($act == SmsService::ACT_LOGIN && !$exists) {
            return $this->msg('手机号尚未注册!');
        } else if ($act == SmsService::ACT_PWD_RESET && !$exists) {
            return $this->msg('手机号尚未注册!');
        }

        $result = SmsService::send($act, $cc, $mobile);
        if ($result !== true) {
            return $this->msg($result);
        }
        return $this->msg('成功发送', 1);
    }

    public function sendTest()
    {
        try {
//            $apiUri = 'https://sms-api.luosimao.com/v1/send.json';

//            $payload = [
//                'auth' => ['api', self::API_KEY],
//                'form_params' => [
//                    'mobile' => '13170555414',
//                    'message' => '測試隨機驗證碼: ' . Str::random(6) . ' 【YOU品】',
//                ],
//            ];

//            dd($payload);

//            info('sms payload: ' . var_export($payload, true));

//            $result = Http::withBasicAuth('api', 'key-' . self::API_KEY)
//                ->asForm()
//                ->post($apiUri, $payload);
//
//            dd($result->json());

//            $client = new Client([
//                'timeout' => 25,
//            ]);
//
//            $result = $client->request('POST', $apiUri, $payload);
//            dd(json_decode($result->getBody()->getContents()));

            $result = SmsService::sendToCN('13170555414', '測試隨機驗證碼: ' . Str::random(6) . ' 【YOU品】');

            return $result;

        } catch (\Exception $e) {
            report($e);
            return response()->json(['status' => 0, 'msg' => 'error']);
        }
    }
}

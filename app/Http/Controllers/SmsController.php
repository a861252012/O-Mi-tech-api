<?php

namespace App\Http\Controllers;

use App\Facades\SiteSer;
use App\Models\Users;
use App\Services\I18n\PhoneNumber;
use App\Services\Sms\SmsService;
use GuzzleHttp\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class SmsController extends Controller
{
    public function send(Request $req)
    {
        $cc = $req->post('cc');
        $mobile = $req->post('mobile');
        $act = $req->post('act');

        // input validation
        if (empty($cc) || empty($mobile) || empty($act)) {
            return $this->msg(__('messages.Sms.send.invalid_request'));
        }
        $mobile = PhoneNumber::formatMobile($cc, $mobile);

        // check reg mobile exists
        $cc_mobile = $cc.$mobile;
        $site_id = SiteSer::siteId();
        $exists = Users::where('cc_mobile', $cc_mobile)->where('site_id', $site_id)->exists();

        if ($act == SmsService::ACT_REG && $exists) {
            return $this->msg(__('messages.Sms.send.the_phone_has_been_use'));
        } else if ($act == SmsService::ACT_LOGIN && !$exists) {
            return $this->msg(__('messages.Sms.send.not_registered'));
        } else if ($act == SmsService::ACT_PWD_RESET && !$exists) {
            return $this->msg(__('messages.Sms.send.not_registered'));
        }

        $result = SmsService::send($act, $cc, $mobile);
        if ($result !== true) {
            return $this->msg($result);
        }
        return $this->msg(__('messages.Sms.send.send_success'), 1);
    }
}

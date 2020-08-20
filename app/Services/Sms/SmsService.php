<?php

namespace App\Services\Sms;

use App\Facades\SiteSer;
use App\Services\I18n\PhoneNumber;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class SmsService
{
    const KEY_PREFIX = 'smscode:';
    const KEY_EXPIRES = 180;

    const ACT_REG = 1;
    const ACT_LOGIN = 2;
    const ACT_PWD_RESET = 3;
    const ACT_PWD_RESET_SEND = 4;
    const ACT_MODIFY_MOBILE = 5;

    // error messages
    const ERR_INVALID_FORMAT = '手机号格式错误';
    const ERR_ALREADY_SEND = '刚刚已发送过，请稍后再试';
    const ERR_SEND_FAILED = '发送失败，请稍后再试';
    const ERR_VERIFY_FAILED = '验证号码错误';

    // World wide
    const WW_API_SEND_URL = 'https://intapi.253.com/send/json';
//    const WW_API_ACCOUNT = 'I5060113'; // 创蓝API账号
//    const WW_API_PASSWORD = 'ecXFtYiQu4e5bf';// 创蓝API密码
    const WW_API_ACCOUNT = 'I6660467'; // 创蓝API账号
    const WW_API_PASSWORD = 'kPmEYeBJ348778';// 创蓝API密码

    // CN

    /* 创蓝 */
//    const CN_API_SEND_URL = 'https://smssh1.253.com/msg/send/json'; //创蓝发送短信接口URL
//    const CN_API_ACCOUNT = 'N4461667'; // 创蓝API账号
//    const CN_API_PASSWORD = 'LYZyT9Le';// 创蓝API密码
//    const CN_API_ACCOUNT_2 = 'N8124476';   // 创蓝API账号
//    const CN_API_PASSWORD_2 = '6WjcgI06i'; // 创蓝API密码

    /* luosimao */
    const CN_API_SEND_URL = 'https://sms-api.luosimao.com/v1/send.json'; //luosimao接口URL
    const CN_API_ACCOUNT = 'api';   // 创蓝API账号
    const CN_API_PASSWORD = '9e4a9df8a3b3ab0ef5f1552f1ab66ab8'; // 创蓝API密码

//    const TPL_REG = "【直播秀场】验证码：「{{code}}」，注册验证码，请您尽快完成注册。";  // 模板須審核，請勿隨意更動
//    const TPL_LOGIN = "【直播秀场】验证码：「{{code}}」，您的登录验证码。";             // 模板須審核，請勿隨意更動
//    const TPL_PWD_RESET = "【直播秀场】验证码：「{{code}}」，用于密码找回。";           // 模板須審核，請勿隨意更動
//    const TPL_PWD_RESET_SEND = "【直播秀场】新密码：「{{pwd}}」。";                    // 模板須審核，請勿隨意更動
//    const TPL_MODIFY_MOBILE = "【直播秀场】验证码：「{{code}}」，用于手机号变更。";     // 模板須審核，請勿隨意更動

    const TPL_REG = "验证码：「{{code}}」，注册验证码，请您尽快完成注册。【玉夕郎商贸】"; // 模板須審核，請勿隨意更動
    const TPL_LOGIN = "验证码：「{{code}}」，您的登录验证码。【玉夕郎商贸】";             // 模板須審核，請勿隨意更動
    const TPL_PWD_RESET = "验证码：「{{code}}」，用于密码找回。【玉夕郎商贸】";           // 模板須審核，請勿隨意更動
    const TPL_PWD_RESET_SEND = "新密码：「{{pwd}}」。【玉夕郎商贸】";                     // 模板須審核，請勿隨意更動
    const TPL_MODIFY_MOBILE = "验证码：「{{code}}」，用于手机号变更。【玉夕郎商贸】";     // 模板須審核，請勿隨意更動

    public static function resetPwd($cc, $mobile, $pwd)
    {
        $act = self::ACT_PWD_RESET_SEND;
        if (!PhoneNumber::checkFormat($cc, $mobile)) {
            return __('messages.Password.pwdResetByMobile.err_invalid_format');
        }
        if (self::exists($act, $cc, $mobile)) {
            return __('messages.Password.pwdResetByMobile.err_verify_failed');
        }

        // send
        if ($cc != '999') {
            $msg = str_replace('{{pwd}}', $pwd, self::TPL_PWD_RESET_SEND);
            if ($cc == '86') {
                $result = self::sendToCN($mobile, $msg);
            } else {
                $result = self::sendToWW($cc.$mobile, 'Password:'.$pwd);
            }
        }

        // log
        self::log($act, $cc, $mobile, $pwd);

        return true;
    }

    public static function send($act, $cc, $mobile, $checkFormat = true)
    {
        if ($checkFormat && !PhoneNumber::checkFormat($cc, $mobile)) {
            return __('messages.Password.pwdResetByMobile.err_invalid_format');
        }
        if (self::exists($act, $cc, $mobile)) {
            return __('messages.SmsService.try_again_later');
        }

        // gen random number
        $code = mt_rand(100000, 999999);

        // send
        if ($cc != '999') {
            switch ($act) {
                case self::ACT_REG:
                    $tpl = __('messages.SmsService.register_msg_verify');
                    $msg = str_replace('{{code}}', $code, $tpl);
                    break;

                case self::ACT_LOGIN:
                    $tpl = __('messages.SmsService.login_msg_verify');
                    $msg = str_replace('{{code}}', $code, $tpl);
                    break;

                case self::ACT_PWD_RESET:
                    $tpl = __('messages.SmsService.reset_pwd_msg_verify');
                    $msg = str_replace('{{code}}', $code, $tpl);
                    break;

                case self::ACT_MODIFY_MOBILE:
                    $tpl = __('messages.SmsService.reset_phone_msg_verify');
                    $msg = str_replace('{{code}}', $code, $tpl);
                    break;
            }
            if ($cc == '86') {
                $result = self::sendToCN($mobile, $msg);
            } else {
                $result = self::sendToWW($cc.$mobile, 'Code:'. $code);
            }
            // TODO: log result error
            // {"code": "0", "error":"", "msgid":"1102849617877536768"}
        }

        // write redis
        self::saveCode($act, $cc, $mobile, $code);

        // log
        self::log($act, $cc, $mobile, $code);

        return true;
    }

    public static function verify($act, $cc, $mobile, $code, $checkFormat = true)
    {
        if ($checkFormat && !PhoneNumber::checkFormat($cc, $mobile)) {
            return __('messages.Password.pwdResetByMobile.err_invalid_format');
        }

        // read redis
        $send_code = self::readCode($act, $cc, $mobile);

        if (empty($send_code) || $send_code != $code) {
            return __('messages.Password.pwdResetByMobile.err_verify_failed');
        }

        return true;
    }

    public static function saveCode($act, $cc, $mobile, $code)
    {
        $redisKey = self::KEY_PREFIX . $cc . $mobile .':'. $act;
        $data = [
            'ts' => time(),
            'c' => $code,
        ];
        Redis::set($redisKey, json_encode($data), 'EX', self::KEY_EXPIRES);
    }

    public static function readCode($act, $cc, $mobile)
    {
        $redisKey = self::KEY_PREFIX . $cc . $mobile .':'. $act;
        $dataStr = Redis::get($redisKey);
        if (is_null($dataStr)) {
            return '';
        }
        $data = json_decode($dataStr, true);
        if (time() - $data['ts'] > self::KEY_EXPIRES) {
            return '';
        }
        return isset($data['c']) ? $data['c'] : '';
    }

    public static function exists($act, $cc, $mobile)
    {
        $redisKey = self::KEY_PREFIX . $cc . $mobile .':'. $act;
        $dataStr = Redis::get($redisKey);
        if (is_null($dataStr)) {
            return false;
        }
        $data = json_decode($dataStr, true);
        if (time() - $data['ts'] < 60) {
            return true;
        }
        return false;
    }

    public static function log($act, $cc, $mobile, $code)
    {
        $log_file = '/data/iev4code/smslog/sms_log.txt';
        if (!file_exists($log_file)) {
            return;
        }

        $len = strlen($mobile);
        if ($len >= 8) {
            $mobile = substr($mobile, 0, 4) . str_pad('', $len - 7, '*')
                . substr($mobile, -3);
        } elseif ($len >= 4) {
            $mobile = substr($mobile, 0, $len - 3) . str_pad('', 3, '*');
        }
        $log = date('Y-m-d H:i:s '). $act .' '. $cc . $mobile. ' '. $code. "\n";
        $logs_str = trim(file_get_contents($log_file));
        $logs = array_slice(explode("\n", $logs_str), 0, 20);
        array_unshift($logs, $log);

        file_put_contents($log_file, join("\n", $logs));
    }

    public static function sendToWW($mobile, $msg, $needstatus = 'true')
    {
//        $postArr = array(
//            'account'  => self::WW_API_ACCOUNT,
//            'password' => self::WW_API_PASSWORD,
//            'msg' => $msg,
//            'mobile' => $mobile,
//            'report' => $needstatus,
//        );
        $postArr = [
            'json' => [
                'account'  => self::WW_API_ACCOUNT,
                'password' => self::WW_API_PASSWORD,
                'mobile' => $mobile,
                'msg' => $msg,
                'report' => $needstatus,
            ]
        ];

        $result = self::curlPost(self::WW_API_SEND_URL, $postArr);
        return $result;
    }

    public static function sendToCN($mobile, $msg, $needstatus = 'true')
    {
        $postArr = [
            'auth' => [self::CN_API_ACCOUNT, self::CN_API_PASSWORD],
            'form_params' => [
                'mobile' => $mobile,
                'message' => $msg,
            ],
        ];

//        $postArr = array(
//            'message' => $msg,
//            'phone' => $mobile,
//            'report' => $needstatus,
//        );


        $result = self::curlPost(self::CN_API_SEND_URL, $postArr);
        return $result;
    }

        /**
     * 通过CURL发送HTTP请求
     * @param string $url  //请求URL
     * @param array $postFields //请求参数
     * @return mixed
     *
     */
//    public static function curlPost($url, $postFields)
//    {
//        $postFields = json_encode($postFields);
//        $ch = curl_init();
//        curl_setopt($ch, CURLOPT_URL, $url);
//        curl_setopt(
//            $ch,
//            CURLOPT_HTTPHEADER,
//            [
//                'Content-Type: application/json; charset=utf-8',
//            ]
//        );
//        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
//        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//        curl_setopt($ch, CURLOPT_POST, 1);
//        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
//        curl_setopt($ch, CURLOPT_TIMEOUT, 25);
//        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
//        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
//        $ret = curl_exec($ch);
//        if (false == $ret) {
//            $result = curl_error($ch);
//        } else {
//            $rsp = curl_getinfo($ch, CURLINFO_HTTP_CODE);
//            if (200 != $rsp) {
//                $result = "请求状态 ". $rsp . " " . curl_error($ch);
//            } else {
//                $result = $ret;
//            }
//        }
//        curl_close($ch);
//        return $result;
//    }

    /* 使用guzzle http 發送請求 */
    public static function curlPost($url, $postFields)
    {
        $client = new Client([
            'timeout' => 25,
        ]);

        $result = $client->request('POST', $url, $postFields);
//        dd(json_decode($result->getBody()->getContents()));
        if (200 != $result->getStatusCode()) {
            \Log::error("请求状态错误:  " . var_export($result, true));
            return __('messages.SmsService.curlPost_error') . $result->getStatusCode();
        }

        return $result->getBody()->getContents();
    }

    public static function getAcct()
    {
        return SiteSer::config('sms_acct');
    }
//    public static function useNew()
//    {
//        return self::getAcct() == "2";
//    }
}

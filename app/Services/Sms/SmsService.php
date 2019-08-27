<?php

namespace App\Services\Sms;

use App\Facades\SiteSer;
use Illuminate\Support\Facades\Redis;

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
    const WW_API_ACCOUNT = 'I5060113'; // 创蓝API账号
    const WW_API_PASSWORD = 'ecXFtYiQu4e5bf';// 创蓝API密码

    // CN
    const CN_API_SEND_URL = 'https://smssh1.253.com/msg/send/json'; //创蓝发送短信接口URL
    const CN_API_ACCOUNT = 'N4461667'; // 创蓝API账号
    const CN_API_PASSWORD = 'LYZyT9Le';// 创蓝API密码

    const TPL_REG = "【直播秀场】验证码：「{{code}}」，注册验证码，请您尽快完成注册。";  // 模板須審核，請勿隨意更動
    const TPL_LOGIN = "【直播秀场】验证码：「{{code}}」，您的登录验证码。";             // 模板須審核，請勿隨意更動
    const TPL_PWD_RESET = "【直播秀场】验证码：「{{code}}」，用于密码找回。";           // 模板須審核，請勿隨意更動
    const TPL_PWD_RESET_SEND = "【直播秀场】新密码：「{{pwd}}」。";                    // 模板須審核，請勿隨意更動
    const TPL_MODIFY_MOBILE = "【直播秀场】验证码：「{{code}}」，用于手机号变更。";     // 模板須審核，請勿隨意更動

    static function resetPwd($cc, $mobile, $pwd)
    {
        $act = self::ACT_PWD_RESET_SEND;
        if (!self::checkFormat($cc, $mobile)) {
            return self::ERR_INVALID_FORMAT;
        }
        if (self::exists($act, $cc, $mobile)) {
            return self::ERR_ALREADY_SEND;
        }

        // send
        if ($cc != '999') {
            $msg = str_replace('{{pwd}}', $pwd , self::TPL_PWD_RESET_SEND);
            if ($cc == '86') {
                $result = self::sendToCN($mobile, $msg);
            } else {
                $result = self::sendToWW($cc.$mobile, $msg);
            }
        }

        // log
        self::log($act, $cc, $mobile, $pwd);

        return true;
    }

    static function send($act, $cc, $mobile, $checkFormat = true)
    {
        if ($checkFormat && !self::checkFormat($cc, $mobile)) {
            return self::ERR_INVALID_FORMAT;
        }
        if (self::exists($act, $cc, $mobile)) {
            return self::ERR_ALREADY_SEND;
        }

        // gen random number
        $code = mt_rand(100000, 999999);

        // send
        if ($cc != '999') {
            switch ($act) {
                case self::ACT_REG:
                    $msg = str_replace('{{code}}', $code , self::TPL_REG);
                break;
                case self::ACT_LOGIN:
                    $msg = str_replace('{{code}}', $code , self::TPL_LOGIN);
                break;
                case self::ACT_PWD_RESET:
                    $msg = str_replace('{{code}}', $code , self::TPL_PWD_RESET);
                break;
                case self::ACT_MODIFY_MOBILE:
                    $msg = str_replace('{{code}}', $code , self::TPL_MODIFY_MOBILE);
                break;
            }
            if ($cc == '86') {
                $result = self::sendToCN($mobile, $msg);
            } else {
                $result = self::sendToWW($cc.$mobile, $msg);
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

    static function verify($act, $cc, $mobile, $code, $checkFormat = true)
    {
        if ($checkFormat && !self::checkFormat($cc, $mobile)) {
            return self::ERR_INVALID_FORMAT;
        }

        // read redis
        $send_code = self::readCode($act, $cc, $mobile);

        if (empty($send_code) || $send_code != $code) {
            return self::ERR_VERIFY_FAILED;
        }

        return true;
    }


    static function checkFormat($cc, $mobile)
    {
        // see: https://github.com/giggsey/libphonenumber-for-php

        // china
        if ($cc == '86' && strlen($mobile) !== 11) {
            return false;
        }

        // taiwan
        if ($cc == '886'
            && !((strlen($mobile) == 10 && substr($mobile, 0, 2) === '09')
                || (strlen($mobile) == 9 && substr($mobile, 0, 1) === '9'))
        ) {
            return false;
        }

        return true;
    }

    static function saveCode($act, $cc, $mobile, $code)
    {
        $redisKey = self::KEY_PREFIX . $cc . $mobile .':'. $act;
        $data = [
            'ts' => time(),
            'c' => $code,
        ];
        Redis::set($redisKey, json_encode($data), 'EX', self::KEY_EXPIRES);
    }

    static function readCode($act, $cc, $mobile)
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

    static function exists($act, $cc, $mobile)
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

    static function log($act, $cc, $mobile, $code)
    {
        $log_file = '/data/iev4code/smslog/sms_log.txt';
        if (!file_exists($log_file)) {
            return;
        }

        $len = strlen($mobile);
        if ($len >= 8) {
            $mobile = substr($mobile, 0, 4) . str_pad('', $len - 7, '*')
                . substr($mobile, -3);
        } else if ($len >= 4) {
            $mobile = substr($mobile, 0, $len - 3) . str_pad('', 3, '*');
        }
        $log = date('Y-m-d H:i:s '). $act .' '. $cc . $mobile. ' '. $code. "\n";
        $logs_str = trim(file_get_contents($log_file));
        $logs = array_slice(explode("\n", $logs_str), 0, 20);
        array_unshift($logs, $log);

        file_put_contents($log_file, join("\n", $logs));
    }

    static function sendToWW($mobile, $msg, $needstatus = 'true')
    {
        $postArr = array(
            'account'  => self::WW_API_ACCOUNT,
            'password' => self::WW_API_PASSWORD,
            'msg' => $msg,
            'mobile' => $mobile,
            'report' => $needstatus,
        );
        $result = self::curlPost(self::WW_API_SEND_URL, $postArr);
        return $result;
    }

    static function sendToCN($mobile, $msg, $needstatus = 'true')
    {
        $postArr = array(
            'account'  => self::CN_API_ACCOUNT,
            'password' => self::CN_API_PASSWORD,
            'msg' => $msg,
            'phone' => $mobile,
            'report' => $needstatus,
        );
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
    static function curlPost($url, $postFields)
    {
        $postFields = json_encode($postFields);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            [
                'Content-Type: application/json; charset=utf-8',
            ]
        );
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_TIMEOUT, 25);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $ret = curl_exec($ch);
        if (false == $ret) {
            $result = curl_error($ch);
        } else {
            $rsp = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if (200 != $rsp) {
                $result = "请求状态 ". $rsp . " " . curl_error($ch);
            } else {
                $result = $ret;
            }
        }
        curl_close($ch);
        return $result;
    }
}

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
    const ACT_MODIFY_MOBILE = 4;

    // TODO: error messages
    const ERR_INVALID_FORMAT = 'INVALID_FORMAT';
    const ERR_ALREADY_SEND = 'ALREADY_SEND';
    const ERR_SEND_FAILED = 'SEND_FAILED';
    const ERR_VERIFY_FAILED = 'VERIFY_FAILED';

    static function send($act, $cc, $mobile)
    {
        if (!self::checkFormat($cc, $mobile)) {
            return self::ERR_INVALID_FORMAT;
        }
        if (self::exists($act, $cc, $mobile)) {
            return self::ERR_ALREADY_SEND;
        }

        // gen random number
        $code = mt_rand(100000, 999999);

        // send
        // TODO

        // write redis
        self::saveCode($act, $cc, $mobile, $code);

        // log
        self::log($act, $cc, $mobile, $code);

        return true;
    }

    static function verify($act, $cc, $mobile, $code)
    {
        if (!self::checkFormat($cc, $mobile)) {
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
            && (strlen($mobile) != 10 || substr($mobile, 0, 2) !== '09')
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
        if (getenv('APP_DEBUG') !== 'true' || !file_exists($log_file)) {
            return;
        }
        $len = strlen($mobile);
        if ($len >= 10) {
            $mobile = substr($mobile, 0, 4) . str_pad('', $len - 7, '*')
                . substr($mobile, -3);
        }
        $log = date('Y-m-d H:i:s '). $act .' '. $cc . $mobile. ' '. $code. "\n";
        $logs_str = trim(file_get_contents($log_file));
        $logs = array_slice(explode("\n", $logs_str), 0, 20);
        array_unshift($logs, $log);

        file_put_contents($log_file, join("\n", $logs));
    }

}

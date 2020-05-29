<?php
/**
 * Redis Cache 服務
 * @author Weine
 * @date 2020-05-28
 */

namespace App\Services;


use Illuminate\Support\Facades\Redis;

class RedisCacheService
{
    const SID_PREFIX = 'sid:';

    public function setSidForPC($uid, $val)
    {
        $key = self::SID_PREFIX . $uid;
        $ttl = 7200; //2小時
        Redis::set($key, $val);
        Redis::expire($key, $ttl);
    }

    public function setSidForMobile($uid, $val)
    {
        $key = self::SID_PREFIX . $uid;
        $ttl = 5184000; //60天
        Redis::set($key, $val);
        Redis::expire($key, $ttl);
    }

    public function delSid($uid)
    {
        $key = self::SID_PREFIX . $uid;
        Redis::del($key);
    }

    public function sid($uid)
    {
        $key = self::SID_PREFIX . $uid;
        $result = Redis::get($key);
        if (empty($result)) {
            $result = Redis::hget('huser_sid', $uid);
            if (!empty($result)) {
                $this->setSidForMobile($uid, $result);
                Redis::hdel('huser_sid', $uid);
            }
        }

        return $result;
    }
}
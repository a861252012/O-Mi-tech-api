<?php

namespace App\Traits;

use App\Facades\SiteSer;
use App\Scopes\SiteScope;
use App\Services\RedisCacheService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Created by PhpStorm.
 * User: raby
 * Date: 2018/4/19
 * Time: 14:05
 */
trait GuardExtend
{
    public function updateSid($id, $sid)
    {
        $id = (string)$id;
        $sid = (string)$sid;
        $huser_sid = resolve(RedisCacheService::class)->sid($id);
        if (!empty($huser_sid) && $huser_sid != $sid) {//有可能重复登录了
            //删除旧session，踢出用户在上一个浏览器的登录状态
            Session::getHandler()->destroy($huser_sid);
        }
    }

    //$token 是否已在其它机器登录 1是  0否
    public function checkRepeatLogin($id, $token)
    {
        return $token && Redis::exists("sid:{$id}")
            && resolve(RedisCacheService::class)->sid($id) != $token;
    }
}
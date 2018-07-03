<?php

namespace App\Traits;

use App\Facades\SiteSer;
use App\Scopes\SiteScope;
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
        $huser_sid = Redis::hget('huser_sid', $id);
        if (empty($huser_sid)){ //说明以前没登陆过，没必要检查重复登录
            Redis::hset('huser_sid', $id, $sid);
        } elseif ($huser_sid != $sid) {//有可能重复登录了
            //更新用户对应的sessid
            Redis::hset('huser_sid', $id, $sid);
            //删除旧session，踢出用户在上一个浏览器的登录状态
            Session::getHandler()->destroy($huser_sid);
        }
    }

    //$token 是否已在其它机器登录 1是  0否
    public function checkRepeatLogin($id, $token)
    {
        if ($token && Redis::Hexists('huser_sid', $id) && Redis::hget('huser_sid', $id) != $token){
            throw new HttpResponseException(JsonResponse::create(['status' => 101, 'msg' => '您的账号已经在其他机器登录！']));
        }
        return false;
    }
}
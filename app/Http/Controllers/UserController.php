<?php

namespace App\Http\Controllers;

use App\Models\Pack;
use App\Models\Users;
use App\Services\User\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;

class UserController extends Controller
{
    public function getCurrentUser()
    {
        if (Auth::check()) {
            // 通过用户服务去获取
            /** @var Users $user */
            $user = Auth::user();
            // 格式化用户信息 过滤掉用户的密码之类的敏感信息
            $userInfo = collect($this->getOutputUser($user));
            $userInfo->put('points', $user->points);
            if (resolve(UserService::class)->getUserHiddenPermission($userInfo)) {
                $userInfo['hidden'] = $user['hidden'];
            }
            // 获取用户等级提升还需要的级别
            $levelInfo = $this->getLevelByRole($user);
            $userInfo = $userInfo->union($levelInfo);

            $userInfo['mails'] = resolve('messageService')->getMessageNotReadCount($user['uid'], $user['lv_rich']);

            // 是贵族才验证 下掉贵族状态
            if ($user['vip'] && ($user['vip_end'] < date('Y-m-d H:i:s'))) {
                resolve(UserService::class)->cancelVip(Auth::id());
                $userInfo['vip'] = 0;
                $userInfo['vip_end'] = '';
            }

        }
        return JsonResponse::create(['status' => 1, 'data' => $userInfo]);
    }

    public function following()
    {
        return JsonResponse::create(Redis::zrevrange('zuser_attens:' . Auth::id(), 0, -1));
    }
}

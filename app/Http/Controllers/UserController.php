<?php

namespace App\Http\Controllers;

use App\Models\Users;
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
            $user=Auth::user();
            // 格式化用户信息 过滤掉用户的密码之类的敏感信息
            $userInfo = $this->getOutputUser($user);
            if (resolve('userServer')->getUserHiddenPermission($userInfo)) {
                $userInfo['hidden'] = $user['hidden'];
            }
            // 获取用户等级提升还需要的级别
            $levelInfo = $this->getLevelByRole($user);
            $userInfo['lv_current_exp'] = $levelInfo['lv_current_exp'];
            $userInfo['lv_next_exp'] = $levelInfo['lv_next_exp'];
            $userInfo['lv_percent'] = $levelInfo['lv_percent'];
            $userInfo['lv_sub'] = $levelInfo['lv_sub'];
            $userInfo['mails'] = resolve('messageService')->getMessageNotReadCount($user['uid'], $user['lv_rich']);
        }
        return JsonResponse::create(['status' => 1, 'data' => $userInfo]);
    }
}

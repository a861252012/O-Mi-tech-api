<?php

namespace App\Services\Task\GiftScript;

use App\Models\Users;
use App\Services\User\UserService;

class Points extends GiftBase implements GiftInterface
{

    /**
     * 送钻石
     *
     * @param $gift
     * @param $uid
     * @return mixed
     */
    public function present($gift, $uid)
    {
        $userService = resolve(UserService::class);
        $userinfo = $userService->getUserInfo($uid);

        $points = $userinfo['points'] + (int)$gift;
        $result = $userService->updateUserInfo($uid, ['points' => $points]);

        return $result;
    }

}
<?php

namespace App\Services\Task\GiftScript;

use App\Models\Users;
use App\Services\User\UserService;

class Icon extends GiftBase implements GiftInterface
{
    /**
     * 添加用户
     *
     * @param $gifts
     * @param $uid
     */
    public function present($gifts, $uid)
    {
        $gift = $gifts[0];
        $data = [
            'icon_id' => $gift['id'],
        ];
        $result = resolve(UserService::class)->updateUserInfo($uid, $data);

        return $result;
    }
}
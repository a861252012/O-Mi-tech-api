<?php

namespace App\Service\Task\GiftScript;

use App\Models\UserIcon;

class Medals implements GiftInterface
{
    /**
     * 添加用户的礼物到背包中
     *
     * @param $gifts
     * @param $uid
     */
    public function present($gifts,$uid)
    {
        // 暂时性的屏蔽掉
        return true;
        $gift = $gifts[0];
        $data = array(
            'uid'=>$uid,
            'icon_id'=>$gift['id'],
            'type'=>1,
            'init_time'=>date('Y-m-d H:i:s'),
            'dml_flag'=>1
        );
        UserIcon::create($data);
    }
}
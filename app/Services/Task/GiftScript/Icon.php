<?php

namespace App\Services\Task\GiftScript;

use App\Models\Users;
class Icon extends GiftBase implements GiftInterface
{
    /**
     * 添加用户
     *
     * @param $gifts
     * @param $uid
     */
    public function present($gifts,$uid)
    {
        $gift = $gifts[0];
        $data = array(
            'icon_id'=> $gift['id']
        );
        $result = Users::where('uid',$uid)->update($data);
        if($result !== false){
            $redis = $this->getredis();
            $redis->hset('huser_info:'.$uid,'icon_id',$gift['id']);
            return true;
        }
        return false;
    }
}
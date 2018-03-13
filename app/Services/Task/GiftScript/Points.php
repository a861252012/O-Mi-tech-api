<?php

namespace App\Services\Task\GiftScript;

use App\Models\Users;

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
        $redis = $this->getredis();
        $userinfo = $redis->hGetAll('huser_info:' . $uid);

        $points = $userinfo['points'] + $gift;
        $result = Users::where('uid',$uid)->update(array('points' => $points));

        if ($result !== false) {
            $redis->hset('huser_info:' . $uid, 'points', $points);
            return true;
        }
        return false;
    }

}
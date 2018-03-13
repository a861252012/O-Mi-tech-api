<?php

namespace App\Services\Task\GiftScript;

use App\Models\Users;
use App\Models\UserMexp;

class Level extends GiftBase implements GiftInterface
{

    /**
     * 提升用户的等级多少级
     *
     * @param $gift int 提升等级
     * @param $uid int 用户uid
     */
    public function present($gift, $uid)
    {
          $redis = $this->getredis();
          $userinfo = $redis->hGetAll('huser_info:' . $uid);

        // 判断是否升级之后就超过了最大的等级了
        if (($userinfo['lv_rich'] + $gift) >= 31) {
            $lv_rich = 31;
        } else {
            $lv_rich = $userinfo['lv_rich'] + $gift;
            $lvs = $this->getLvRich();
            $rich = $lvs[$lv_rich]['level_value'];
        }

        $data = array(
            'lv_rich' => $lv_rich,
            'rich' => $rich
        );

        $result = Users::where('uid', $uid)->update($data);

        // 更新用户的等级的redis
        if ($result !== false) {
            $redis->hset('huser_info:' . $uid, 'lv_rich', $lv_rich);
            $redis->hset('huser_info:' . $uid, 'rich', $rich);
            // 记录日志
            $exp_log = array(
                'uid' => $uid,
                'exp' => $rich - $userinfo['rich'],
                'type' => 1,
                'status' => 2,
                'roled' => $userinfo['roled'],
                'admin_id' => 10000,
                'init_time' => date('Y-m-d H:i:s'),
                'dml_time' => date('Y-m-d H:i:s'),
                'dml_flag' => 1,
                'curr_exp' => $userinfo['rich']
            );
            UserMexp::create($exp_log);
            return true;
        }
        return false;
    }

}
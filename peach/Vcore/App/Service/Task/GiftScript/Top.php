<?php

namespace App\Service\Task\GiftScript;

use App\Models\Users;
use App\Models\UserMexp;
class Top extends GiftBase implements GiftInterface
{

    /**
     * 提升用户的等级 到 多少级
     *
     * @param $gift int 提升等级
     * @param $uid int 用户uid
     */
    public function present($gift,$uid)
    {
        $redis=$this->getredis();
        $userinfo = $redis->hGetAll('huser_info:' . $uid);

        // 判断是否要提升到的等级是否小于本身用户的等级了，如果小于就不提升了
        if($userinfo['lv_rich']  >= $gift){
            return true;
        }else{
            $lv_rich = $gift;
            // 根据等级计算 对应的值
            $lvs = $this->getLvRich();
            $rich = $lvs[$lv_rich]['level_value'];
        }

        $data = array(
            'lv_rich' => $lv_rich,
            'rich'=>$rich
        );

        $result = Users::where('uid',$uid)->update($data);

        // 更新用户的等级的redis
        if($result !== false){
            $this->_redisInstance->hset('huser_info:' . $uid, 'lv_rich', $lv_rich);
            $this->_redisInstance->hset('huser_info:' . $uid, 'rich', $rich);
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
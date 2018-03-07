<?php

namespace App\Service\Task\TaskScript;

use App\Models\UserCheckSign;
use App\Models\TaskUser;
class CheckSign extends ScriptBase implements ScriptInterface
{

    /**
     * 检查签到任务
     *
     * @param array $task
     * @param int $uid
     *
     * @return mixed
     */
    public function check($task, $uid)
    {
        $this->task = $task;
        $this->uid = $uid;

        /**
         * 签到任务是否以前签过到了
         */
        $check_sign = UserCheckSign::where('uid',$uid)->get();

        if (!$check_sign) {
            return 'can_apply';
        }

        if ($task['relatedid']) {
            $isDo = TaskUser::where('relatedid',$task['relatedid'])->where('uid',$user['uid'])->first();
            // 当没有申请或者没有完成父任务时
            if (!$isDo || $isDo['status'] != 1) {
                return 'can_apply';
            }
        }

        /**
         * 计算差值
         */
        $s = date('Ymd',time())-date('Ymd',strtotime($check_sign['last_time']));
        if($s==0){
            return 'all';
        }

        if($s >= 1){
            return 'can_apply';
        }
    }

    public function checkCsc()
    {

    }
}
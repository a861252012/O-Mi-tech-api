<?php

namespace App\Services\Task\TaskScript;


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


        if ($task['relatedid']) {
            $isDo = TaskUser::where('relatedid',$task['relatedid'])->where('uid',$uid)->first();
            // 当没有申请或者没有完成父任务时
            if (!$isDo || $isDo['status'] != 1) {
                return 'can_apply';
            }
        }


    }

    public function checkCsc()
    {

    }
}
<?php

namespace App\Services\Task\TaskScript;

use App\Models\TaskUser;
use App\Models\Users;
use App\Models\UserBuyGroup;

class OpenVip extends ScriptBase implements ScriptInterface
{


    /**
     * 检查首次开通贵族的任务
     *
     * @param $task
     * @param $uid
     *
     * @return mixed
     */
    public function check($task, $uid)
    {
        $this->task = $task;
        $this->uid = $uid;

        $userTask = TaskUser::where('vtask_id',$task['vtask_id'])->where('uid',$uid)->first();
        $user = Users::find($uid);

        if ($task['pre_vtask_id']) {
            $isDo = TaskUser::where('vtask_id',$task['pre_vtask_id'])->where('uid', $user['uid'])->first();
            // 当没有申请或者没有完成父任务时
            if (!$isDo || $isDo['status'] != 1) {
                return 'can_apply';
            }
        }

        /**
         * 如果没有接任务的话，就判断是否可以自动申请任务
         */
        if (!$userTask) {

            /**
             * 只要用户满足做这个任务的条件就要插入一条数据 等待开通贵族的验证
             */
            $c = serialize(array('csc' => 0, 'update_time' => time()));
            $data = array(
                'uid' => $uid,
                'vtask_id' => $task['vtask_id'],
                'status' => 0,
                'csc' => $c,
                'apply_date' => time(),
                'init_time' => date('Y-m-d H:i:s'),
                'dml_time' => date('Y-m-d H:i:s'),
                'dml_flag' => 1
            );
            TaskUser::create($data);
            // 增加一个申请人数
            $this->updateTaskApplicants($task);
            return 'doing';
        }

        $ut = $userTask;
        $csc = unserialize($ut['csc']);
        if ($ut['status'] == 1) {
            return 'all';
        }

        if ($ut['status'] == -1) {
            return 'failed';
        }

        if ($ut['status'] == 0 && $csc['csc'] == 100) {
            return 'success';
        }

        /**
         * 当没有完成的时候要检查进度
         */
        if ($ut['status'] == 0 && $csc['csc'] != 100) {
            /**
             * 检查是否完成了开通贵族，是就更新状态
             * 要求在申请任务之后 开通贵族的才算
             */
            $invite = UserBuyGroup::where('create_at','>',date('Y-m-d H:i:s',$ut['apply_date']))->where('uid',$uid)->get();
            if ($invite) {
                $data = array();
                $csc['csc'] = 100;
                $csc['update_time'] = time();
                $data['csc'] = serialize($csc);
                TaskUser::where('auto_id',$ut['auto_id'])->update($data);
                return 'success';
            }
            return 'doing';
        }
    }

    public function checkCsc()
    {

    }
}
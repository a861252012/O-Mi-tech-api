<?php

namespace App\Http\Controllers;

use App\Services\Task\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class TaskController extends Controller
{

    /**
     * 获取一个登陆用户的所有的可以做的项目的
     */
    public function index()
    {

        $online = Auth::id();
        $taskService = resolve(TaskService::class);
        if (!$online) {
            $user_task = $taskService->getAllTask();
        } else {
            $taskService->setUid($online);
            $user_task = $taskService->getAllUserCanTask();
        }
        $task = [];
        $data = [];
        foreach ($user_task as $value) {
            $data[$value['script_name']][] = $value;
        }
        // 临时处理为时间戳，后期前台可能会用到
        $task['data'] = $data;
        return new JsonResponse($task);
    }

    public function test($id)
    {
        $msg = $this->container->make('messageServer');
        $ms = $msg->getMessageByUid(Auth::id());
        return $this->render('Member/msglist1', ['data' => $ms]);
    }

    /**
     * 领取任务完成的奖励
     *  /task/end/(16)
     * @param $task_id int 任务id
     * @return JsonResponse
     */
    public function billTask($task_id)
    {
        $online = Auth::id();


        $taskService = resolve(TaskService::class);
        $taskService->setUid($online);
        $flag = $taskService->billTask($task_id);

        if ($flag) {
            return new JsonResponse(['status' => 1, 'msg' => '领取成功！']);
        } else {
            return new JsonResponse(['status' => 0, 'msg' => '领取失败！请查看任务是否完成或已经领取过了！']);
        }
    }


}
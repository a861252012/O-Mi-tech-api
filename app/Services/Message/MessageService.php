<?php

namespace App\Services\Message;

use App\Models\Messages;
use App\Services\Service;
use DB;
use App\Facades\SiteSer;

class MessageService extends Service
{

    /**
     * 私信类型消息
     */
    const USER_MESSAGE = 2;

    /**
     * 系统消息
     */
    const SYSTEM_MESSAGE = 1;

    /**
     * 发送  一对一 私信消息
     *
     * @param $data array arrray{
     *  send_uid
     *  rec_uid
     *  content
     * @return mix
     */
    public function sendUserToUserMessage($data = [])
    {
        /**
         * 必须发送用户 内容不能为空
         */
        if (!isset($data['send_uid']) || !isset($data['rec_uid']) || !isset($data['content'])) {
            throw new \Exception('Content and rec_uid can not empty for send user message!');
        }
        // 发送私人用户
        if (!isset($data)) {
            $data['category'] = self::USER_MESSAGE;
        }

        $data['created'] = date('Y-m-d H:i:s');
        return DB::table('video_mail')->insertGetId($data);
    }

    /**
     * 发送 系统消息 到所有用户
     * @param $data array
     *
     * @return mix
     */
    public function sendSystemToUsersMessage($data = [])
    {
        if (!isset($data['rec_uid']) || !isset($data['content'])) {
            throw new \Exception('Content and rec_uid can not empty for send system message!');
        }
        // 系统默认用户0
        if (!isset($data['send_uid'])) {
            $data['send_uid'] = 0;
        }

        // 系统消息 1
        if (!isset($data['category'])) {
            $data['category'] = self::SYSTEM_MESSAGE;
        }
        $data['created'] = date('Y-m-d H:i:s');
        $data['site_id'] =  SiteSer::siteId();
        return DB::table('video_mail')->insertGetId($data);
    }

    /**
     * 根据用户id获取所有的消息数据 分页
     *
     * @param $uid int 用id
     * @param int $num 分页的条数
     * @return mixed
     */
    public function getMessageByUid($uid, $num = 10)
    {
        $msg = Messages::where('rec_uid', $uid)->orderBy('id', 'desc')->paginate($num);
        return $msg;
    }

    /**
     * 根据用户id 和 消息类型 获取对应的数据 分页
     *
     * @param $uid int 用id
     * @param $type int 消息类型id 默认2为用户私信
     * @param int $num 条数
     * @return mixed
     */
    public function getMessageByUidAndType($uid, $type = self::USER_MESSAGE, $num = 10, $lv_flag = 0)
    {
        if ($type == self::USER_MESSAGE) {
            $msg = Messages::where('rec_uid', $uid)->with('sendUser')
                ->where('category', $type)
                ->where('logicflag','!=',0)
                ->orderBy('id', 'desc')
                ->paginate($num);
        } elseif ($type == self::SYSTEM_MESSAGE) {
            $endtime = date('Y-m-d H:i:s');
            $msg = Messages::with('sendUser')->whereRaw('(rec_uid = ? or (rec_uid=0 and lv_flag = ? and endtime>=?)) and category = ?', array($uid, $lv_flag,$endtime, $type))
                ->orderBy('id', 'desc')
                ->paginate($num);
        }
        return $msg;
    }

    /**
     * 修改消息的 阅读与否 的状态
     *
     * @param $uid
     * @return bool
     * 更新消息状态
     * @update by Young  移除私信
     */
    public function updateMessageStatus($uid)
    {
        // TODO 私信的状态的修改 以后要抽象起来 最好是需求上单独来更新每条私信状态 而不是批量
        //移除私信 By Young
        
        // if ($category == self::USER_MESSAGE) {
        //     Messages::where('rec_uid', $uid)->where('status', 0)
        //         ->where('category', $category)
        //         ->where('logicflag', 1)
        //         ->update(array('status' => 1));
        // }

        // 系统消息的状态
        //if ($category == self::SYSTEM_MESSAGE) {
        // 首先修改单独发给用户的系统消息
        
        // update by Young status:1 已读。 status:0 未读
        Messages::where('rec_uid', $uid)->where('status', 0)
            ->where('category', self::SYSTEM_MESSAGE)
            ->where('logicflag', 1)
            ->update(array('status' => 1));

        // 再修改批量发送消息的状态的 此处用的redis修改
        $msgs = Messages::where('rec_uid', 0)->where('category', self::SYSTEM_MESSAGE)
            ->where('created', '>', date('Y-m-d H:i:s', time() - 30 * 24 * 60 * 60))// 一个月的过期
            ->get();
        if (!$msgs) {
            return true;
        }
        foreach ($msgs as $msg) {
            $this->container->make('redis')->hset('hmail:' . $msg->id, $uid, time());
        }
        //}
        return true;
    }

    /**
     * 获取用户未读的消息数量
     */
    public function getMessageNotReadCount($uid, $lv_flag = 0)
    {
        // 检查群发消息是否读过了---
        $res = Messages::where('rec_uid', $uid)
            ->where('status',0)
            ->where('logicflag',1)
            ->count();

        $systemMsg= Messages::where('rec_uid',0)->where('category',1)
            ->where('lv_flag',$lv_flag)		//BUG 系统消息未接收者未作等级过滤
            ->where('logicflag',1)
            ->where('created','>',date('Y-m-d H:i:s',time()-30*24*60*60))
            ->get();

        $systemNum = 0;//未读群发消息的
        if($systemMsg){
            foreach($systemMsg as $id){
                $status = $this->make('redis')->hGet('hmail:'.$id['id'],$uid);
                if(!$status){
                    $systemNum ++;
                }
            }
        }
        $res += $systemNum;
        if($res>99){
            return '99';
        }
        return $res;
    }
}

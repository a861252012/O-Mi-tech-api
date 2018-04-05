<?php

namespace App\Services\UserGroup;

use App\Models\LevelRich;
use App\Models\Messages;
use App\Models\Recharge;
use App\Models\UserBuyGroup;
use App\Models\UserGroup;
use App\Models\Users;
use App\Models\VideoMail;
use App\Models\VideoPack;
use App\Services\Service;
use DB;
use Illuminate\Support\Facades\Redis;

class UserGroupService extends Service
{

    /**
     * 根据类型type获取用户组
     * @param $type
     */
    public function getGroupByType($type)
    {

    }

    /**
     *  获取所有公有的用户组 用于可以卖的
     */
    public function getPublicGroup()
    {
        $list = UserGroup::with('permission')->with('g_mount')
            ->where('system', '!=', 'private')
            ->where('type', 'special')
            ->where('dml_flag','!=','3')
            ->orderBy('level_id')
            ->get();
        if (!$list) {
            return [];
        }
        return $this->formatSystem($list);
    }

    /**
     * 获取基础用户等级分组信息
     *
     * @return array
     */
    public function getLevelGroup()
    {
        $lvs = UserGroup::where('type','member')
            ->where('dml_flag','!=',3)
            ->get();
        $data = array();
        foreach($lvs as $lv){
            $data[$lv['level_id']] = $lv;
        }
        return $data;
    }

    /**
     * 格式化system字段
     * @param $data
     * @return mixed
     */
    protected function formatSystem($data)
    {
        foreach ($data as &$value) {
            $value['system'] = unserialize($value['system']);
        }
        return $data;
    }


    /**
     *  检测用户的贵族保级状态 TODO 以后移到新框架中去
     *
     *  如果不是贵族 直接返回
     *  如果是贵族，检测状态：
     *      达到状态更新贵族的有效期
     *      未达到直接返回
     *
     * @param object $user
     * @return boolean
     */
    public function checkUserVipStatus($user)
    {
        // 当不是贵族时
        if (!$user['vip']) {
            return true;
        }

        $group = LevelRich::where('level_id', $user['vip'])->first();
        if (!$group) {
            return true;// 用户组都不在了没保级了
        }

        $userGid=$group->gid;

        // 获取购买记录
        $log = UserBuyGroup::where('uid', $user['uid'])->where('gid',$userGid)->orderBy('end_time', 'desc')->first();
        // 获取充值详细 时间为有效期往前推一个月

        //dc修改空数据下 判断
        $startTime = $log ? strtotime($log->end_time) - 30 * 24 * 60 * 60 : time();


        // 兼容后台充值的策略的
        $pays = Recharge::where('uid', $user['uid'])->where('pay_status', 2)->whereIn('pay_type',[1,4,7])->where('created', '>=', date('Y-m-d H:i:s', $startTime))
            ->sum('points');
        if (!$pays) {
            return true; // 未充值直接返回
        }


        $system = unserialize($group->system);
        if ($pays >= $system['keep_level']) {
            // 更改有效期
            //开启事务
            DB::begintransaction();
            try{
                $newTime = strtotime($log->end_time) + 30 * 24 * 60 * 60;
                $log->end_time = date('Y-m-d H:i:s', $newTime);
                $log->save();

                $userObj = Users::find($user['uid']);
                $userObj->vip_end = date('Y-m-d H:i:s', $newTime);
                $userObj->save();

                //发送私信给用户
                VideoMail::create(array(
                    'send_uid' => 0,
                    'rec_uid' => $user['uid'],
                    'content' => '贵族保级成功提醒：您的' . $group->level_name . '贵族保级成功啦！到期日：' . date('Y-m-d H:i:s', $newTime),
                    'category' => 1,
                    'status' => 0,
                    'created' => date('Y-m-d H:i:s'),
                ));
                DB::commit();
                // 更新完刷新redis
                $this->make('redis')->hset('huser_info:' . $user['uid'], 'vip_end', date('Y-m-d H:i:s', $newTime));
            }catch(\Exception $e){
                \Log::info("保级异常：getmypid ".getmypid()."checkUserVipStatus 更新数据成功  \n");
                DB::rollBack();//事务回滚
            }

        }
        return true;
    }

    /**
     * 根据id获取用户组
     * 格式化
     *
     * @param $gid
     * @return array|mixed
     */
    public function getGroupById($gid)
    {
        $group = UserGroup::with('permission')->with('g_mount')->find($gid);
        if (!$group) {
            return [];
        }

        $group->system = unserialize($group->system);
        return $group;
    }


    public function cancelVip($uid=0){
        $user['uid'] = $uid;
        Users::query()->where('uid',$user['uid'])->update(array('vip'=>0,'vip_end'=>'','hidden'=>0));
        Redis::hmset('huser_info:'.$user['uid'],[
            'vip'=>'0',
            'hidden'=>'0',
            'vip_end'=>'',
        ]);
        $pack = VideoPack::query()->where('uid',$user['uid'])->whereBetween('gid',[120101,120107])->delete();
        Redis::del('user_car:'.$user['uid']);
        return true;
    }
    public function checkVip($uid=0){

    }
    /**
     * 根据id获取用户组
     * 格式化
     *
     * @param $gid
     * @return array|mixed
     */
    public function getGroupByLevelIdAndType($leveid,$type)
    {
        $group = UserGroup::with('permission')->where('level_id',$leveid)
            ->where('dml_flag','!=','3')
            ->orderBy('level_id')
            ->where('type',$type)->first();
        if (!$group) {
            return [];
        }

        $group->system = unserialize($group->system);
        return $group;
    }

}
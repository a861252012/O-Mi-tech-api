<?php
/**
 * Created by PhpStorm.
 * User: raby
 * Date: 2018/3/16
 * Time: 9:05
 */

namespace App\Services\Charge;


use App\Models\Recharge;
use App\Models\RechargeConf;
use App\Models\RechargeWhiteList;
use App\Services\Service;
use App\Services\User\UserService;

class ChargeGroupService extends Service
{
    private $whitelist = null;

    public function channel($uid): array
    {
        $RechargeTypes = $this->groupByUid($uid)['recharge_type'];
        $rechargeTypeArr = $RechargeTypes ? @unserialize($RechargeTypes) : [];
        $rs = [];
        foreach ($rechargeTypeArr as $mode_type=>$v){
            $temp = $v;
            $temp['id'] = $mode_type;
            array_push($rs,$temp);
        }
        return $rs;
    }

    /**
     * 用户所在充值渠道
     * @param int $uid
     */
    public function groupByUid(int $uid = 0): array
    {
        $rechargeGroup = $this->group();
        if ($this->isWhite($uid)) {
            //白名单根据后台白单配置
            return $rechargeGroup[1];
        }
        if ($this->isBlack($uid)) {
            // 黑名单根据后台黑名单配置
            return $rechargeGroup[2];
        }

        //因为要加上统计后台充值数据，所以改从video_recharge充值记录进行统计
        $paymoney = Recharge::where('uid', $uid)
            ->where('pay_status', Recharge::SUCCESS)//统计已成功记录
            ->where(function ($query) {
                $query->orWhere('pay_type', 1)->orWhere('pay_type', 4);//4=只统计银行充值和后台充值记录
            })->sum('paymoney');

        $user = resolve(UserService::class)->getUserByUid($uid);
        //循环匹配充值组
        $RechargeTypes = [
            'isopen'=>0,
        ];
        foreach ($rechargeGroup as $rid => $val) {
            if ($rid < 3) continue; //黑名单、白名单不进入循环

            if ($val['recharge_min'] <= $paymoney && $paymoney <= $val['recharge_max']) {

                //判断充值时间
                $created = strtotime($user['created']);
                $regTimeMax = time() - $val['reg_time_max'] * 86400;
                $regTimeMin = time() - $val['reg_time_min'] * 86400;

                //判断充值时间,充值金额权限
                if ($created <= $regTimeMin && $created >= $regTimeMax) {
                    $RechargeTypes = $val;
                }
            }

        }
        return $RechargeTypes;
    }

    public function group(): array
    {
        $rechargeGroup = $this->make('redis')->get('recharge_group');
        if (!$rechargeGroup) {
            $group = RechargeConf::where('dml_flag', '!=', 3)->get()->toArray();
            // 格式化数组格式 array('[id]'=>array())
            $rechargeGroup = array();
            foreach ($group as $value) {
                $rechargeGroup[$value['auto_id']] = $value;
            }
            $this->make('redis')->set('recharge_group', json_encode($rechargeGroup));
        } else {
            //还原数组
            $rechargeGroup = json_decode($rechargeGroup, true);
        }
        return $rechargeGroup;
    }

    public function isWhite(int $uid = 0) :bool
    {
        $whiteList = $this->whitelist($uid);
        if(!$whiteList) return false;
        return $whiteList->type === 0;
    }

    public function whitelist($uid)
    {
        return $this->whitelist ?:
            $this->whitelist = RechargeWhiteList::where('uid', $uid)->where('dml_flag', '!=', 3)->first();
    }

    public function isBlack(int $uid = 0)
    {
        $whiteList = $this->whitelist($uid);
        if(!$whiteList) return false;
        return $whiteList->type === 1;
    }

    public function close(int $uid): bool
    {
        $item = $this->groupByUid($uid);
        return $item['isopen'] == RechargeConf::CLOSE;
    }

    public function customer(int $uid): bool
    {
        $item = $this->groupByUid($uid);
        return $item['isopen'] == RechargeConf::CUSTOMER;
    }

    public function leave(int $uid): bool
    {
        return !$this->online($uid);
    }

    public function online(int $uid): bool
    {
        $item = $this->groupByUid($uid);
        return $item['isopen'] == RechargeConf::ONLINE;
    }
}
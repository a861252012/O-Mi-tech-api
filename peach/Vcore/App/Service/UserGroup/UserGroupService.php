<?php

namespace App\Service\UserGroup;

use App\Models\Messages;
use App\Models\UserGroup;
use App\Models\Users;
use Core\Service;

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
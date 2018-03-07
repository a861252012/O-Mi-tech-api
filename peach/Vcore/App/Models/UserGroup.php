<?php
namespace App\Models;

class UserGroup extends \Illuminate\Database\Eloquent\Model
{
    protected  $table='video_level_rich';
    protected $primaryKey = 'gid';

    public function permission()
    {
        return $this->hasOne('App\Models\UserGroupPermission','gid','gid');
    }

    public function g_mount()
    {
        // ���������õ�����mount ��Ӧ������Ʒ����gid
        return $this->hasOne('App\Models\Goods','gid','mount');
    }

}
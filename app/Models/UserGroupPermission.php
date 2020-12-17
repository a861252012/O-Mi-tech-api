<?php
namespace App\Models;

class UserGroupPermission extends \Illuminate\Database\Eloquent\Model
{
    protected  $table='video_group_permission';
    protected $primaryKey = 'gid';

    public function userGroup()
    {
        return $this->belongsTo('App\Models\UserGroup', 'gid', 'gid');
    }
}
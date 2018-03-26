<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Users extends Authenticatable
{
    use Notifiable;

    protected $table = 'video_user';
    protected $primaryKey = 'uid';
    protected $guarded = ['uid'];
    public $timestamps = false;

    /**
     * 关联的用户的贵族的信息
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function vipGroup()
    {
        return $this->hasOne('App\Models\UserGroup', 'level_id', 'vip');
    }

    /**
     * 关联的用户的普通等级的数据
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function lvGroup()
    {
        return $this->hasOne('App\Models\UserGroup', 'level_id', 'lv_rich');
    }

    public function banned()
    {
        return $this->status != 1;
    }
}
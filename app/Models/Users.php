<?php

namespace App\Models;

use App\Traits\SiteSpecific;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Users extends Authenticatable
{
    use Notifiable,
        SiteSpecific;

    public $timestamps = false;
    protected $table = 'video_user';
    protected $primaryKey = 'uid';
    protected $guarded = ['uid'];
    protected $hidden = [];

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

    public function isHost()
    {
        return $this->roled == 3;
    }

    public function userShare()
    {
        return $this->hasMany('App\Entities\UserShare', 'share_uid', 'uid');
    }
}
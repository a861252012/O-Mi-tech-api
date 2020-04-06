<?php
/**
 * 用戶推廣清單 實體
 * @author Weine
 * @date 2020-3-30
 */
namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

class UserShare extends Model
{
    protected $table = 'video_user_share';

    public function userInfo()
    {
        return $this->hasOne('App\Models\Users', 'uid', 'uid');
    }

    public function ShareUserInfo()
    {
        return $this->belongsTo('App\Models\Users', 'uid', 'share_uid');
    }
}

<?php
/**
 * 守護用戶開通紀錄 實體
 * @author Weine
 * @date 2020/02/18
 */
namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

class Guardian extends Model
{
    protected $table = 'video_guardian';

    public function guardianSetting()
    {
        return $this->hasOne('App\Entities\GuardianSetting', 'id', 'guard_id');
    }

    public function user()
    {
        return $this->belongsTo('App\Models\Users', 'uid', 'uid');
    }
}

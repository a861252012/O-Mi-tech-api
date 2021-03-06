<?php
namespace App\Models;

use App\Traits\SiteSpecific;
class UserCommission extends \Illuminate\Database\Eloquent\Model
{
    use  SiteSpecific;
    protected $table='video_user_commission';
    protected $primaryKey = 'auto_id';
    protected $guarded = ['auto_id'];
    public $timestamps= false;

    public function user()
    {
        return $this->hasOne('App\Models\Users', 'uid', 'r_uid');
    }

    public function userGroup()
    {
        return $this->hasOne('App\Models\UserGroup','gid','r_id');
    }
}
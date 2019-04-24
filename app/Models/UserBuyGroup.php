<?php
namespace App\Models;

use App\Traits\SiteSpecific;
/**
 * 用户购买用户组的记录
 *
 * Class Messages
 * @package App\Models
 */
class UserBuyGroup extends \Illuminate\Database\Eloquent\Model
{
    /**
     * 表名 消息表
     * @var string
     */
    //use  SiteSpecific;
    protected  $table='video_user_buy_group';
    protected $primaryKey = 'auto_id';

    protected $guarded = ['auto_id'];

    public $timestamps= false;

    public function group()
    {
        return $this->hasOne('App\Models\UserGroup','gid','gid');
    }

}
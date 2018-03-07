<?php
namespace App\Models;

/**
 * 用户购买用户组的记录
 *
 * Class Messages
 * @package App\Models
 */
class UserExtends extends \Illuminate\Database\Eloquent\Model
{
    /**
     * 表名 消息表
     * @var string
     */
    protected  $table='video_user_extends';
    protected $primaryKey = 'uid';

    protected $guarded = ['uid'];

    public $timestamps= false;

}
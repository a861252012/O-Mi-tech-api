<?php
namespace App\Models;

/**
 * 用户修改昵称的记录
 *
 * Class Messages
 * @package App\Models
 */
class UserModNickName extends \Illuminate\Database\Eloquent\Model
{
    /**
     * 表名 修改用户名表
     * @var string
     */
    protected  $table='video_user_mod_nickname';
    protected $primaryKey = 'auto_id';

    protected $guarded = ['auto_id'];

    public $timestamps= false;


}
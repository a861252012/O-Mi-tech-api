<?php
/**
 * Created by PhpStorm.
 * User: irwin
 * Date: 2016/3/23
 * Time: 16:40
 */
 
namespace App\Models;


class UserMexp extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'video_user_mexp';
    protected $primaryKey='auto_id';
    protected $guarded = ['auto_id'];
    public $timestamps=false;

}

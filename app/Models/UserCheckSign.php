<?php
/**
 * Created by PhpStorm.
 * User: irwin
 * Date: 2016/3/23
 * Time: 17:00
 */
 
namespace App\Models;


class UserCheckSign extends \Illuminate\Database\Eloquent\Model
{
    protected $table= 'video_user_check_sign';
    protected $primaryKey='auto_id';
    public $timestamps=false;

}

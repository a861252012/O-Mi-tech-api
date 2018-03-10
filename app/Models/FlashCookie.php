<?php
/**
 * Created by PhpStorm.
 * User: irwin
 * Date: 2016/3/23
 * Time: 15:56
 */
 
namespace App\Models;


class FlashCookie extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'video_flash_cookie';
    protected $primaryKey = 'id';
    protected $guarded = ['id'];
    public $timestamps= false;

}

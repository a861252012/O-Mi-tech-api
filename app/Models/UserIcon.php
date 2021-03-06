<?php
/**
 * Created by PhpStorm.
 * User: irwin
 * Date: 2016/3/23
 * Time: 16:34
 */
 
namespace App\Models;

use App\Traits\SiteSpecific;

class UserIcon extends \Illuminate\Database\Eloquent\Model
{

    use   SiteSpecific;

    protected $table='video_user_icon';
    protected $primaryKey='auto_id';
    protected $guarded = ['auto_id'];
    public $timestamps = false;

}

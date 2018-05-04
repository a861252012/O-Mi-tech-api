<?php
/**
 * Created by PhpStorm.
 * User: nicholas
 * Date: 2017/3/29
 * Time: 13:28
 */

namespace App\Models;

use App\Traits\SiteSpecific;
use Illuminate\Database\Eloquent\Model;

class UserLoginLog extends Model
{
    use   SiteSpecific;

    protected $table='video_user_login_logs';
    protected $guarded=['id'];
    public $timestamps=false;

}
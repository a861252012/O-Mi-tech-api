<?php
/**
 * Created by PhpStorm.
 * User: irwin
 * Date: 2016/3/23
 * Time: 16:40
 */
 
namespace App\Models;

use App\Traits\SiteSpecific;

class UserMexp extends \Illuminate\Database\Eloquent\Model
{
    use   SiteSpecific;
    protected $table = 'video_user_mexp';
    protected $primaryKey='auto_id';
    protected $guarded = ['auto_id'];
    public $timestamps=false;

}

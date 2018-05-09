<?php
/**
 * Created by PhpStorm.
 * User: irwin
 * Date: 2016/3/23
 * Time: 16:11
 */
 
namespace App\Models;

use App\Traits\SiteSpecific;
class TaskUser extends \Illuminate\Database\Eloquent\Model
{
    use SiteSpecific;
    protected $table = 'video_task_user';
    protected $primaryKey = 'auto_id';
    protected $guarded = ['auto_id'];
    public $timestamps=false;

}

<?php
/**
 * Created by PhpStorm.
 * User: irwin
 * Date: 2016/3/23
 * Time: 15:56
 */
 
namespace App\Models;
use App\Traits\SiteSpecific;

class Task extends \Illuminate\Database\Eloquent\Model
{
    use SiteSpecific;
    protected $table = 'video_task';
    protected $primaryKey = 'vtask_id';
    public $timestamps= false;

}

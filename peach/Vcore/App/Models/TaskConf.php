<?php
/**
 * Created by PhpStorm.
 * User: irwin
 * Date: 2016/3/23
 * Time: 14:46
 */
 
namespace App\Models;

class TaskConf extends \Illuminate\Database\Eloquent\Model{
    protected $table = 'video_task_conf';

    protected $primaryKey = 'auto_id';

    public $timestamps= false;


}

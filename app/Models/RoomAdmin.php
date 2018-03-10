<?php

namespace App\Models;

/**
 * [房间管理员表]
 *
 * @author dc
 * @version 20151117
 * @package App\Models
 */
class RoomAdmin extends \Illuminate\Database\Eloquent\Model
{
    /**
     * 表名 房间管理员表
     * @var string
     */
    protected $table = 'video_manage';
    protected $primaryKey = 'auto_id';
    public $timestamps= false;



    public function user()
    {
    	return $this->hasOne('App\Models\Users', 'uid', 'uid');
    }

}




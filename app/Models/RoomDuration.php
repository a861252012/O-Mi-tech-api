<?php
namespace App\Models;

/**
 * 用户预约模型
 *
 * Class Messages
 * @package App\Models
 */
class RoomDuration extends \Illuminate\Database\Eloquent\Model
{
    /**
     * 表名 预约表
     * @var string
     */
    protected  $table='video_room_duration';
    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    public $timestamps= false;

    protected $appends = array('endtime');

    public function setEndtimeAttribute(){
    }

    public function getEndtimeAttribute()
    {
        return date('Y-m-d H:i:s',strtotime($this->starttime)+$this->duration);
    }
}
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

    public function anchor()
    {
        return $this->hasOne('App\Models\Users','uid','uid')->allSites();
    }
}
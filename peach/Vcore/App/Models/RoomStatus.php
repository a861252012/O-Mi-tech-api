<?php
namespace App\Models;

/**
 * 房间状态表对应的模型
 *
 * Class Messages
 * @package App\Models
 */
class RoomStatus extends \Illuminate\Database\Eloquent\Model
{
    /**
     * 表名 房间状态表
     * @var string
     */
    protected  $table='video_room_status';
    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    public $timestamps= false;

    /**
     * 用户信息
     * 一对一
     *
     * <p>
     * 关联用户表
     * 在获取时用 with('sendUser') 用于twig 中可以直接调用
     * </p>
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function sendUser()
    {
        return $this->hasOne('App\Models\Users','uid','uid');
    }
}
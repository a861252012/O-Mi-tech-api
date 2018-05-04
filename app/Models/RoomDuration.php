<?php
namespace App\Models;

use App\Traits\SiteSpecific;

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
    use   SiteSpecific;
    protected  $table='video_room_duration';
    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    public $timestamps= false;
}
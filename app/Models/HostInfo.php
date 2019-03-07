<?php
namespace App\Models;

/**
 * 用户购买用户组的记录
 *
 * Class Messages
 * @package App\Models
 */
class HostInfo extends \Illuminate\Database\Eloquent\Model
{
    /**
     * 表名 消息表
     * @var string
     */
    protected  $table='v4_video_bos.vbos_host_info';
    protected $primaryKey = 'uid';

    protected $guarded = ['uid'];

    public $timestamps= false;

}
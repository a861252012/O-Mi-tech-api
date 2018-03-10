<?php
namespace App\Models;

/**
 * 星座表对应的模型
 *
 * Class Messages
 * @package App\Models
 */
class BirthStar extends \Illuminate\Database\Eloquent\Model
{
    /**
     * 表名 消息表
     * @var string
     */
    protected  $table='video_birth_star';
    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    public $timestamps= false;

}
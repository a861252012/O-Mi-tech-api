<?php
namespace App\Models;

/**
 * 充值分组配置表模型
 *
 * Class RechargeConf
 * @package App\Models
 */
class RechargeConf extends \Illuminate\Database\Eloquent\Model
{
    /**
     * 表名 充值分组配置表
     * @var string
     */
    protected  $table='video_recharge_conf';
    protected $primaryKey = 'auto_id';

    protected $guarded = ['auto_id'];

    public $timestamps= false;

}
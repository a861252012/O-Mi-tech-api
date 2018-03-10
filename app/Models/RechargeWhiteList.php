<?php
namespace App\Models;

/**
 * 充值对应的白名单黑名单
 *
 * Class RechargeWhiteList
 * @package App\Models
 */
class RechargeWhiteList extends \Illuminate\Database\Eloquent\Model
{
    /**
     * 充值黑白名单表
     * @var string
     */
    protected  $table='video_recharge_whitelist';
    protected $primaryKey = 'auto_id';

    protected $guarded = ['auto_id'];

    public $timestamps= false;

}
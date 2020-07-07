<?php
/**
 * 充值的 IP 黑名单
 *
 * Class RechargeBlockIp
 * @package App\Entities
 */
namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

class RechargeBlockIp extends Model
{
    protected $table = 'video_recharge_blockip';

    protected $fillable = ['ip', 'modified'];

    protected $primaryKey = 'ip';

    public $timestamps= false;
}

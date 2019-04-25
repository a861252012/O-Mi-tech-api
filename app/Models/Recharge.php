<?php

namespace App\Models;

use App\Traits\SiteSpecific;
use Illuminate\Database\Eloquent\Model;

/**
 * 送钱记录对应的模型
 *
 * Class Recharge
 * @package App\Models
 */
class Recharge extends Model
{
    use  SiteSpecific;
    const PAY_TYPE_OWN = 50;
    const SUCCESS = 2;
    const PAY_TYPE_CHONGTI = 1;
    public $timestamps = false;
    /**
     * 表名 消息表
     * @var string
     */
    protected $table = 'video_recharge';
    protected $primaryKey = 'id';
    protected $guarded = ['id'];

}
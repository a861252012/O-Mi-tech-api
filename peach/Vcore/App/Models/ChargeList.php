<?php
namespace App\Models;

/**
 * 充值信息
 *
 * Class Messages
 * @package App\Models
 */
class ChargeList extends \Illuminate\Database\Eloquent\Model
{
    /**
     * 表名 充值表
     * @var string
     */
    protected  $table='video_charge_list';
    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    public $timestamps= false;

}
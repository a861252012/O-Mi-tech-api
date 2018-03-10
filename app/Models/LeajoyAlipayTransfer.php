<?php
namespace App\Models;

class LeajoyAlipayTransfer extends \Illuminate\Database\Eloquent\Model
{
    protected  $table='video_pay_leajoy_alipay_transfer';
    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    public $timestamps= false;
}
<?php
namespace App\Models;

class PayNotice extends \Illuminate\Database\Eloquent\Model
{
    protected  $table='video_pay_notice';
    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    public $timestamps= false;

}
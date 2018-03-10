<?php
namespace App\Models;

class MobileUseLogs extends \Illuminate\Database\Eloquent\Model
{
    protected  $table='video_mobile_use_logs';
    protected $primaryKey = 'id';

    public $timestamps = false;
    public $guarded = ['create_at'];
}
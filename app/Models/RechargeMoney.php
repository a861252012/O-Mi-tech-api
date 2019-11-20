<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RechargeMoney extends Model
{
    protected $table = 'video_recharge_money';
    protected $primaryKey = 'id';
    protected $guarded = ['id'];
    protected $fillable = ['recharge_min', 'recharge_max', 'recharge_type', 'client'];
    public $timestamps = true;
}

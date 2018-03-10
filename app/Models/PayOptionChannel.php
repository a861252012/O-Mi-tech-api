<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayOptionChannel extends Model
{
    public $timestamps = false;
    protected $table = 'video_pay_option_channels';
    protected $guarded=['id'];
}

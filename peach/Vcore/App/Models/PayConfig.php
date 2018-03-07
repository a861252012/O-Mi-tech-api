<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class PayConfig extends Model
{
    public $timestamps = true;
    protected $table = 'video_pay_config';
    protected $guarded=['id'];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class PayGD extends \Illuminate\Database\Eloquent\Model
{
    use SoftDeletes;

    protected $table = 'video_pay_gd';
    protected $primaryKey = 'id';
    public $timestamps = true;
    public $guarded = ['id'];
}

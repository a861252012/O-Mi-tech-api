<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\SiteSpecific;
class PayGD extends \Illuminate\Database\Eloquent\Model
{
    use SoftDeletes;
    use SiteSpecific;
    protected $table = 'video_pay_gd';
    protected $primaryKey = 'id';
    public $timestamps = true;
    public $guarded = ['id'];
}

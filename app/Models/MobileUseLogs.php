<?php
namespace App\Models;
use App\Traits\SiteSpecific;
class MobileUseLogs extends \Illuminate\Database\Eloquent\Model
{
    use SiteSpecific;
    protected  $table='video_mobile_use_logs';
    protected $primaryKey = 'id';

    public $timestamps = false;
    public $guarded = ['create_at'];
}
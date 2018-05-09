<?php
namespace App\Models;

use App\Traits\SiteSpecific;
class ActiveCommon extends \Illuminate\Database\Eloquent\Model
{
    use SiteSpecific;
    protected  $table='video_active_common';
    protected $primaryKey = 'activity_id';
}
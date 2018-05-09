<?php
namespace App\Models;
use App\Traits\SiteSpecific;

class Active extends \Illuminate\Database\Eloquent\Model
{
    use SiteSpecific;
    protected  $table='video_active';
    protected $primaryKey = 'active_id';
}
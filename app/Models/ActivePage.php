<?php
namespace App\Models;

use App\Traits\SiteSpecific;
class ActivePage extends \Illuminate\Database\Eloquent\Model
{
    use SiteSpecific;
    protected  $table='video_active_page';
    protected $primaryKey = 'id';
}
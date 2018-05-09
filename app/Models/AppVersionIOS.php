<?php
namespace App\Models;

use App\Traits\SiteSpecific;
class AppVersionIOS extends \Illuminate\Database\Eloquent\Model
{
    use SiteSpecific;
    protected  $table='video_app_versions_ios';
    protected $hidden=['updated_at','deleted_at'];
}
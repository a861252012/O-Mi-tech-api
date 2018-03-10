<?php
namespace App\Models;

class AppVersionIOS extends \Illuminate\Database\Eloquent\Model
{
    protected  $table='video_app_versions_ios';
    protected $hidden=['updated_at','deleted_at'];
}
<?php
namespace App\Models;

class AppVersion extends \Illuminate\Database\Eloquent\Model
{
    protected  $table='video_app_versions';
    protected $hidden=['updated_at','deleted_at'];
}
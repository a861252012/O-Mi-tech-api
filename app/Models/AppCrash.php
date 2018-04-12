<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppCrash extends Model
{
    protected  $table='video_app_crashes';
    protected $primaryKey = 'crash_id';
    public $timestamps=false;
    protected $guarded=['crash_id'];
}
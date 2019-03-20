<?php
namespace App\Models;

class Usersall extends \Illuminate\Database\Eloquent\Model
{
    protected $table='video_user';
    protected $primaryKey = 'uid';
    protected $hidden=['updated_at','deleted_at'];
}
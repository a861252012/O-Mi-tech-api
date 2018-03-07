<?php
namespace App\Models;

class UserInvitation extends \Illuminate\Database\Eloquent\Model
{
    protected $table='video_user_invitation';
    protected $primaryKey = 'id';
    public $timestamps= false;
    protected $guarded = ['id'];

}
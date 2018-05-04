<?php
namespace App\Models;

use App\Traits\SiteSpecific;

class UserInvitation extends \Illuminate\Database\Eloquent\Model
{
    use   SiteSpecific;

    protected $table='video_user_invitation';
    protected $primaryKey = 'id';
    public $timestamps= false;
    protected $guarded = ['id'];

}
<?php
namespace App\Models;

class InviteCodes extends \Illuminate\Database\Eloquent\Model
{
    protected $table='video_invite_codes';
    protected $primaryKey = 'id';

    public $timestamps = false;

    public function group()
    {
        return $this->hasOne('\\App\\Models\\InviteGroup','id', 'gid');
    }


    public function user()
    {
        return $this->hasOne('\\App\\Models\\User', 'uid', 'uid');
    }
}
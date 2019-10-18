<?php

namespace App\Models;

class UserSignin extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'video_user_signin';
    protected $primaryKey = 'uid';
    protected $guarded = [];
    public $timestamps = false;
    public $incrementing = false;
}

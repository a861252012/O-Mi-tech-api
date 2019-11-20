<?php

namespace App\Models;

class UserSigninHistory extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'video_user_signin_history';
    protected $primaryKey = ['uid', 'signin_date'];
    protected $guarded = [];
    public $timestamps = false;
    public $incrementing = false;
}

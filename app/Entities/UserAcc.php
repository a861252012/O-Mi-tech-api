<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

class UserAcc extends Model
{
	protected $table = 'video_user_acc';

	public function User()
	{
		return $this->belongsTo('App\Entities\User', 'uid');
	}
}
<?php

namespace App\Models;

/**
 * summary
 */
class Lottery extends \Illuminate\Database\Eloquent\Model
{
	protected $table = 'video_lottery';

	protected $primaryKey = 'id';

	protected $guarded = ['id'];

 	public $timestamps= false;

}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImagesText extends Model
{
    public $timestamps = false;
    protected $table = 'video_images_text';
    protected $primaryKey = 'img_text_id';
    protected $fillable = [];

}

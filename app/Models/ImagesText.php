<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\SiteSpecific;

class ImagesText extends Model
{
    use SiteSpecific;
    public $timestamps = false;
    protected $table = 'video_images_text';
    protected $primaryKey = 'img_text_id';
    protected $fillable = [];

}

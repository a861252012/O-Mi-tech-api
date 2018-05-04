<?php
namespace App\Models;

use App\Traits\SiteSpecific;
class ActivityPag extends \Illuminate\Database\Eloquent\Model
{

    use   SiteSpecific;
    protected  $table='video_images_text';
    protected $primaryKey = 'img_text_id';
}
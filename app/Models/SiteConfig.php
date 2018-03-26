<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteConfig extends Model
{
    protected $table = 'video_site_configs';
    protected $guarded = ['id'];
    public $timestamps = false;
}

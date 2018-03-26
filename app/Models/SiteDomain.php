<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteDomain extends Model
{
    protected $table = 'video_site_domains';
    protected $guarded = ['id'];
    public $timestamps = false;
}

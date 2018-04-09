<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Site extends Model
{
    protected $table = 'video_sites';
    protected $guarded = ['id'];
    public $timestamps = false;

    public function config()
    {
        return $this->hasMany(SiteConfig::class,'site_id','id');
    }

    public function domains()
    {
        return $this->hasMany(SiteDomain::class,'site_id','id');
    }
}

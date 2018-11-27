<?php
namespace App\Models;

use App\Traits\SiteSpecific;
class Redirect extends \Illuminate\Database\Eloquent\Model
{
    //2018-11-27： 经领导clark确认，需求作变更： 变更为代理区分一二站
    use SiteSpecific;
    protected $table='video_redirect';
    protected $primaryKey = 'id';
    public $timestamps= false;

    public function scopeNormal($query)
    {
        return $query->where('status',0);
    }
}
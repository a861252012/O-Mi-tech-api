<?php
namespace App\Models;

class Redirect extends \Illuminate\Database\Eloquent\Model
{
    protected $table='video_redirect';
    protected $primaryKey = 'id';
    public $timestamps= false;

    public function scopeNormal($query)
    {
        return $query->where('status',0);
    }
}
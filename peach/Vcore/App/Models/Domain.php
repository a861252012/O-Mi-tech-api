<?php
namespace App\Models;

class Domain extends \Illuminate\Database\Eloquent\Model
{
    protected $table='video_domain';
    protected $primaryKey = 'id';
    public $timestamps= false;
    public function scopeNormal($query)
    {
        return $query->where('status',0);
    }
    public function agent(){
        return $this->hasOne('App\Models\Agents','did','id');
    }
}
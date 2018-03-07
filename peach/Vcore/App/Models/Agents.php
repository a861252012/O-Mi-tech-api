<?php
namespace App\Models;

class Agents extends \Illuminate\Database\Eloquent\Model
{
    protected $table='video_agents';
    protected $primaryKey = 'id';
    protected $guarded = [];
    public $timestamps= false;
}
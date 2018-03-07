<?php
namespace App\Models;

class AgentsRelationship extends \Illuminate\Database\Eloquent\Model
{
    protected  $table='video_agent_relationship';
    protected $primaryKey = 'id';
    protected $guarded = ['id'];

    public $timestamps = false;
}
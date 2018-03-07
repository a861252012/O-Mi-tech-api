<?php
namespace App\Models;

class ActivityClick extends \Illuminate\Database\Eloquent\Model
{
    protected $table='video_activity_click';
    protected $primaryKey = 'auto_id';
    protected $guarded = ['auto_id'];
    public $timestamps= false;

}

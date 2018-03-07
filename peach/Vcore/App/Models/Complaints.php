<?php
namespace App\Models;

class Complaints extends \Illuminate\Database\Eloquent\Model
{
    protected $table='video_complaints';
    protected $primaryKey = 'id';
    public $timestamps= false;
    protected $guarded = ['id'];

}
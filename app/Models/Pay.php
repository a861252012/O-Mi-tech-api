<?php
namespace App\Models;

class Pay extends \Illuminate\Database\Eloquent\Model
{
    protected  $table='video_pay';
    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    public $timestamps= false;

}
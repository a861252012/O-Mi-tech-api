<?php
namespace App\Models;

use App\Traits\SiteSpecific;
class Complaints extends \Illuminate\Database\Eloquent\Model
{
    use   SiteSpecific;
    protected $table='video_complaints';
    protected $primaryKey = 'id';
    public $timestamps= false;
    protected $guarded = ['id'];

}
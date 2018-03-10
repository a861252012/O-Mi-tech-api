<?php
namespace App\Models;

class VideoMail extends \Illuminate\Database\Eloquent\Model{

    protected $table = 'video_mail';

    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    public $timestamps= false;

}
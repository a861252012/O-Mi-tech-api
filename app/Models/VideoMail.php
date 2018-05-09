<?php
namespace App\Models;
use App\Traits\SiteSpecific;
class VideoMail extends \Illuminate\Database\Eloquent\Model{
    use SiteSpecific;
    protected $table = 'video_mail';

    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    public $timestamps= false;

}
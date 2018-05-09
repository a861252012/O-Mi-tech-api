<?php
namespace App\Models;
use App\Traits\SiteSpecific;
class VideoPack extends \Illuminate\Database\Eloquent\Model{
    use SiteSpecific;
    protected $table = 'video_pack';

    protected $primaryKey = 'uid';

}
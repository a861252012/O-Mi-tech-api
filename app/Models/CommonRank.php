<?php
namespace App\Models;
use App\Traits\SiteSpecific;
class CommonRank extends \Illuminate\Database\Eloquent\Model
{
    use SiteSpecific;
    protected  $table='video_common_rank';
    protected $primaryKey = 'erank_id';
}
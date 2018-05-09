<?php
namespace App\Models;
use App\Traits\SiteSpecific;
class ExtremeRank extends \Illuminate\Database\Eloquent\Model
{
    use SiteSpecific;
    protected  $table='video_extreme_rank';
    protected $primaryKey = 'erank_id';
}
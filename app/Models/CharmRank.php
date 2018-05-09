<?php
namespace App\Models;
use App\Traits\SiteSpecific;
class CharmRank extends \Illuminate\Database\Eloquent\Model
{
    use SiteSpecific;
    protected  $table='video_charm_rank';
    protected $primaryKey = 'crank_id';

    protected function getPointsAttribute() {
        return $this->num;
    }

}
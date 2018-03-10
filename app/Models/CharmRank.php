<?php
namespace App\Models;

class CharmRank extends \Illuminate\Database\Eloquent\Model
{
    protected  $table='video_charm_rank';
    protected $primaryKey = 'crank_id';

    protected function getPointsAttribute() {
        return $this->num;
    }

}
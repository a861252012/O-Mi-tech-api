<?php
//不分站点，用户消费流水表
namespace App\Models;

class GiftList extends \Illuminate\Database\Eloquent\Model
{
    protected  $table='video_mall_list';
    protected $hidden=['updated_at','deleted_at'];
}
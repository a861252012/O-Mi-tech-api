<?php
namespace App\Models;

class RoomOneToMore extends \Illuminate\Database\Eloquent\Model
{
    protected  $table='video_room_one_to_more';
    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    public $timestamps= false;

    /**
     *
     */
    public function user()
    {
        return $this->hasOne('App\Models\Users','uid','uid');
    }

    public function purchase()
    {
        return $this->hasMany('App\Models\UserBuyOneToMore','onetomore','id');
    }

    public function userAll()
    {
        return $this->hasOne('App\Models\Usersall','uid','uid');
    }
}
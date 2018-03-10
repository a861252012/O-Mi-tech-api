<?php
namespace App\Models;

class UserBuyOneToMore extends \Illuminate\Database\Eloquent\Model
{
    protected  $table='video_user_buy_one_to_more';
    protected $primaryKey = 'id';
    protected $guarded = ['id'];
    public $timestamps= false;
}
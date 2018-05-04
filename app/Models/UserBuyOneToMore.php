<?php
namespace App\Models;

use App\Traits\SiteSpecific;
class UserBuyOneToMore extends \Illuminate\Database\Eloquent\Model
{
    use  SiteSpecific;
    protected  $table='video_user_buy_one_to_more';
    protected $primaryKey = 'id';
    protected $guarded = ['id'];
    public $timestamps= false;
}
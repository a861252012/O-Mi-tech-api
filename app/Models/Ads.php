<?php
/**
 * Created by PhpStorm.
 * User: desmond
 * Date: 2018/3/23
 * Time: 11:29
 */

namespace App\Models;

class Ads extends  \Illuminate\Database\Eloquent\Model{
    const DEVICE_PC = 1;
    const DEVICE_ANDROID=2;
    const DEVICE_IOS = 4;
    const IMG_PATH = '/';
    public  $timestamps = false;
    public $table = 'video_ads';
    public $guarded = ['id'];

    public function  getMetaAttribute($value){
        return json_decode($value);
    }
    public function setMetaAttribute($value){
        $this->attributes['mate'] = json_decode($value);
    }

    public function scopePublished($q){
        return   $q->where('published_at','<>','null')->where('published_at','<=',date('Y-m-d H:i:s'));
    }

}


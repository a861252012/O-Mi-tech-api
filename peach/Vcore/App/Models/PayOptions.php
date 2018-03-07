<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayOptions extends Model
{
    public $timestamps = true;
    protected $table = 'video_pay_options';
    protected $guarded = ['id'];

    public function channels()
    {
        return $this->hasMany(PayOptionChannel::class,'option_id','id');
    }

    public function setChannels($cids)
    {
        PayOptionChannel::query()->where('option_id', $this->id)->delete();
        foreach ($cids as $cid) {
            PayOptionChannel::create([
                'cid' => $cid,
                'option_id' => $this->id
            ]);
        }
    }
}

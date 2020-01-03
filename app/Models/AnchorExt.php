<?php

namespace App\Models;

class AnchorExt extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'video_anchor_ext';
    protected $primaryKey = 'uid';
    protected $guarded = [];
    public $timestamps = false;
    public $incrementing = false;
}

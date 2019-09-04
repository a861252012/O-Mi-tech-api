<?php
namespace App\Models;

use App\Traits\SiteSpecific;

class RedEnvelopeSend extends \Illuminate\Database\Eloquent\Model
{
    use SiteSpecific;
    protected $table = 'video_send_red_envelope_record';
    protected $primaryKey = 'auto_send_id';
}
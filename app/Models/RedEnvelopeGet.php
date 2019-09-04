<?php
namespace App\Models;

use App\Traits\SiteSpecific;

class RedEnvelopeGet extends \Illuminate\Database\Eloquent\Model
{
    use SiteSpecific;
    protected $table = 'video_get_red_envelope_record';
    protected $primaryKey = 'auto_get_id';
}
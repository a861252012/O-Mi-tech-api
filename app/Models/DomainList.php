<?php
namespace App\Models;

use App\Traits\SiteSpecific;
class DomainList extends \Illuminate\Database\Eloquent\Model
{
    use   SiteSpecific;
    protected $table='video_domain_list';
    protected $primaryKey = 'id';
    public $timestamps= false;
}
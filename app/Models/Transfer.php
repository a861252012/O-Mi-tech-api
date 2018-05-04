<?php
namespace App\Models;

use App\Traits\SiteSpecific;
/**
 * 转帐记录model
 * @author dc
 * @version 201501112
 */
class Transfer extends \Illuminate\Database\Eloquent\Model
{
    use   SiteSpecific;
    protected $table = 'video_transfer';
    protected $primaryKey = 'auto_id';
}

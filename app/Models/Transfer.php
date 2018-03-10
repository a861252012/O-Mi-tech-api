<?php
namespace App\Models;


/**
 * 转帐记录model
 * @author dc
 * @version 201501112
 */
class Transfer extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'video_transfer';
    protected $primaryKey = 'auto_id';
}

<?php
/**
 * 鑽石交換失敗紀錄 實體
 * @author Weine
 * @date 2020-11-19
 */
namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

class PlatformTransferFailed extends Model
{
    protected $table = 'video_platform_transfer_failed';

    public const UPDATED_AT = false;
}

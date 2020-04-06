<?php
/**
 * 安裝資訊紀錄 實體
 * @author Weine
 * @date 2020-03-25
 */
namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

class InstallLog extends Model
{
    protected $table = 'video_install_log';

    const UPDATED_AT = false;
}

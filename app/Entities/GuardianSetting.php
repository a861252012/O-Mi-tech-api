<?php
/**
 * 守護功能設定 實體
 * @author Weine
 * @date 2020/02/15
 */
namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

class GuardianSetting extends Model
{
    protected $table = 'video_guardian_setting';

    protected $casts = [
        'activate' => 'array',
        'renewal' => 'array',
    ];
}

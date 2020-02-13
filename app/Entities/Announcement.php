<?php
/**
 * 登入公告 實體
 * @author Weine
 * @date 2020/1/2
 */
namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $table = 'video_announcement';

    protected $guarded = ['id'];
}

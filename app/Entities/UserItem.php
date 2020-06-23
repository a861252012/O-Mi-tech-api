<?php
/**
 * 用戶物品紀錄表 實體
 * @date 2020/06/19
 */

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

class UserItem extends Model
{
    protected $table = 'video_user_item';
    protected $hidden = ['status', 'created_at', 'updated_at'];

}
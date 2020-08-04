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

    public function item()
    {
        return $this->hasOne('App\Entities\Item', 'id', 'item_id');
    }
}
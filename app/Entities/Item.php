<?php
/**
 * 背包物品列表 實體
 * @date 2020/06/19
 */

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $table = 'video_item';

    public function userItem()
    {
        return $this->belongsTo('App\Entities\UserItem', 'id', 'item_id');
    }

}
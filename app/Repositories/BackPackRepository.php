<?php
/**
 * 背包功能 資源庫
 * @date 2020/06/19
 */

namespace App\Repositories;

use App\Entities\Item;
use App\Entities\UserItem;
use Illuminate\Support\Facades\Auth;

class BackPackRepository
{
    public function __construct(Item $item, UserItem $userItem)
    {
        $this->item = $item;
        $this->userItem = $userItem;
    }

    public function getUserBackPack($uid)
    {
        $data = UserItem::join('video_item as a', 'a.item_id', '=', 'video_user_item.item_id')
            ->select('video_user_item.*', 'a.item_type', 'a.item_name', 'a.frontend_mode')
            ->where('video_user_item.uid', $uid)
            ->where('video_user_item.status', 0)
            ->get();

        return $data;
    }

    public function updateItemStatus($itemID)
    {
        if (UserItem::where('id', $itemID)->exists()) {
            UserItem::where('id', $itemID)->update(['status' => 1]);

            return ['status' => 1, 'msg' => 'OK'];
        }

        return ['status' => 101, 'msg' => '物品ID有误'];
    }
}
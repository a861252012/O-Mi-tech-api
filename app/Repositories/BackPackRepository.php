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
        return $this->userItem->where('uid', $uid)->where('status', 0)->get();
    }

    public function updateItemStatus($id, $status)
    {
        return UserItem::where('id', $id)->update(['status' => $status]);
    }
}
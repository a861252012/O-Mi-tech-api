<?php
/**
 * 背包功能 資源庫
 * @date 2020/06/19
 */

namespace App\Repositories;

use App\Entities\Item;
use App\Entities\UserItem;

class UserItemRepository
{
    public function __construct(
        Item $item,
        UserItem $userItem
    ) {
        $this->item = $item;
        $this->userItem = $userItem;
    }

    //取得user的背包列表
    public function getUserBackPack($uid)
    {
        return $this->userItem->where('uid', $uid)->where('status', 0)->limit(100)->orderBy('id', 'asc')->get();
    }

    public function getItemById($id)
    {
        return $this->userItem->where('id', $id)->first();
    }

    public function updateItemStatus($id, $status)
    {
        return $this->userItem->where('id', $id)->update(['status' => $status]);
    }

    public function insertGift($gift)
    {
        return $this->userItem->insert($gift);
    }
}

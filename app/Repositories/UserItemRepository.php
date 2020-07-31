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
        return $this->userItem->where('uid', $uid)->where('status', 0)->get();
    }

    public function getItemById($id)
    {
        return $this->userItem->where('id', $id)->first();
    }

    public function updateItemStatus($id, $status)
    {
        $res = $this->userItem->where('id', $id)->update(['status' => $status]);

        if ($res) {
            return ['status' => 1, 'msg' => 'OK'];
        }

        return ['status' => 0, 'msg' => '使用失敗'];
    }

    public function insertGift($gift)
    {
        return $this->userItem->insert($gift);
    }
}

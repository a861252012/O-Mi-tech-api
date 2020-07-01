<?php
/**
 * 背包功能 資源庫
 * @date 2020/06/19
 */

namespace App\Repositories;

use App\Entities\Item;
use App\Entities\UserItem;
use App\Models\LevelRich;

class UserItemRepository
{
    public function __construct(
        Item $item,
        UserItem $userItem,
        LevelRich $levelRich
    ) {
        $this->item = $item;
        $this->userItem = $userItem;
        $this->levelRich = $levelRich;
    }

    //取得user的背包列表
    public function getUserBackPack($uid)
    {
        return $this->userItem->where('uid', $uid)->where('status', 0)->get();
    }

    public function getItemByGid($id)
    {
        return $this->userItem->where('id', $id)->first();
    }

    public function getLevelByGid($gid)
    {
        return $this->levelRich->where('gid', $gid)->first();
    }

    //更新物品狀態
    public function updateItemStatus($id, $status)
    {
        return $this->userItem->where('id', $id)->update(['status' => $status]);
    }
}

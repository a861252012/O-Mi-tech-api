<?php
/**
 * 背包功能 資源庫
 * @date 2020/06/19
 */

namespace App\Repositories;

use App\Entities\Item;
use App\Entities\UserItem;
use App\Models\UserBuyGroup;
use App\Models\LevelRich;
use Illuminate\Support\Facades\Auth;
use App\Facades\SiteSer;

class BackPackRepository
{
    public function __construct(Item $item, UserItem $userItem)
    {
        $this->item = $item;
        $this->userItem = $userItem;
    }

    //取得user的背包列表
    public function getUserBackPack($uid)
    {
        return $this->userItem->where('uid', $uid)->where('status', 0)->get();
    }

    //透過物品的流水id反查item_type
    public function getItemType($id)
    {
        $itemID = $this->userItem->where('id', $id)->value('item_id');

        return $this->item->where('item_id', $itemID)->value('item_type');
    }

    //透過gid反查level相關資訊
    public function getLevelByGid($gid)
    {
        return LevelRich::where('gid', $gid)->first()->toArray();
    }

    //更新物品狀態
    public function updateItemStatus($id, $status)
    {
        return UserItem::where('id', $id)->update(['status' => $status]);
    }

    //寫入貴族紀錄
    public function insertGroupRecord($gid, $vipEnd)
    {
        $level = $this->getLevelByGid($gid);

        $record = array(
            'uid'        => Auth::id(),
            'gid'        => $gid,
            'level_id'   => $level['level_id'],
            'type'       => 4,//操作类型:1 开通,2保级,3赠送 新增type 4:贵族体验券
            'create_at'  => date("Y-m-d H:i:s"),
            'rid'        => Auth::user()->rid ?? 0,
            'status'     => 1,
            'end_time'   => $vipEnd,
            'open_money' => 0,
            'keep_level' => 1500,
            'site_id'    => SiteSer::siteId(),
        );

        return UserBuyGroup::insert($record);
    }
}
<?php
/**
 * 背包功能 服務
 * @date 2020/06/19
 */

namespace App\Services;

use App\Facades\SiteSer;
use App\Repositories\BackPackRepository;
use Illuminate\Support\Facades\Auth;

class BackPackService
{
    protected $backPackRepository;

    public function __construct(BackPackRepository $backPackRepository)
    {
        $this->backPackRepository = $backPackRepository;
    }

    /* 取得背包物品列表 */
    public function getItemList()
    {
        return $this->backPackRepository->getUserBackPack(Auth::user()->uid);
    }

    /* 使用背包物品 */
    public function useItem($itemID)
    {
        return $this->backPackRepository->updateItemStatus($itemID);
    }

}

<?php
/**
 * 背包功能 服務
 * @date 2020/06/19
 */

namespace App\Services;

use App\Http\Resources\BackPack\BackPackResource;
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
        return BackPackResource::collection($this->backPackRepository->getUserBackPack(Auth::id()));
    }

    /* 使用背包物品 */
    public function useItem($id, $status)
    {
        return $this->backPackRepository->updateItemStatus($id, $status);
    }
}

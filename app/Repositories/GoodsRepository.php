<?php

namespace App\Repositories;

use App\Models\Goods;

class GoodsRepository
{
    protected $goods;

    public function __construct(Goods $goods)
    {
        $this->goods = $goods;
    }

    //取得禮物列表,排除推薦禮物
    public function getList()
    {
        return $this->goods->where('is_show', '>', 0)
            ->whereIn('category', [6, 7, 8])
            ->orderBy('sort_order', 'asc')
            ->get();
    }
}

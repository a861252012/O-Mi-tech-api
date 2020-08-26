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
        return $this->goods->where('category', '!=', 1006)
            ->where('is_show', '>', 0)
            ->wherein('category', [1, 3, 4, 5])
            ->orderBy('sort_order', 'asc')
            ->get()
            ->toarray();
    }
}

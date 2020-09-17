<?php
/**
 * 輪盤遊戲 常數
 * @author Weine
 * @date 2020-9-17
 */

namespace App\Constants;


class RouletteItem
{
    /**
     * 輪盤獎勵type => 背包物品id
     *
    3: 金尊體驗券
    4: 紅尊體驗券
    5: 藍尊體驗券
    6: 青尊體驗券
    7: 綠尊體驗券
    8: 墨尊體驗券
    9: 白尊體驗券

     */
    const ITEM_MAP = [
        9 => 1,
        3 => 3,
        4 => 4,
        5 => 5,
        6 => 6,
        7 => 7,
        8 => 8,
    ];
}
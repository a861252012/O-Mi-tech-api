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

    /**
     * 輪盤獎勵type => 輪盤獎項圖示(zimg)
     *
     * 獎項圖示(zimg)
     * 1 鑽石 91a838c0ccff821e322c71dbb5fe74cf
     * 2 經驗值 3a9a06c1b7e961f1a917675c1b94d000
     * 3 金尊體驗券 97dfcc3c9f508d18318c7f79cca3b7e2
     * 4 紅尊體驗券 07af4cbeec9ae21bc1d27ea1e7dfe1a1
     * 5 藍尊體驗券 9cc8e9cf6c6f82e63fe225982b1ed621
     * 6 青尊體驗券 a505da38bfd9a67e9f2b5ce33f3a86cc
     * 7 綠尊體驗券 aeb3b22c55b8862a7fbaa8f880cd9e69
     * 8 墨尊體驗券 98853a7a42ae5ffc2e94b14de2846596
     * 9 白尊體驗券 ea127e540bf5b55f5f9f6b4ce2cf97fd
     * 10 神秘禮物 6e91c040a26b1139122e82d3169604ed
     */

    const ITEM_ICON = [
        1  => '91a838c0ccff821e322c71dbb5fe74cf',
        2  => '3a9a06c1b7e961f1a917675c1b94d000',
        3  => '97dfcc3c9f508d18318c7f79cca3b7e2',
        4  => '07af4cbeec9ae21bc1d27ea1e7dfe1a1',
        5  => '9cc8e9cf6c6f82e63fe225982b1ed621',
        6  => 'a505da38bfd9a67e9f2b5ce33f3a86cc',
        7  => 'aeb3b22c55b8862a7fbaa8f880cd9e69',
        8  => '98853a7a42ae5ffc2e94b14de2846596',
        9  => 'ea127e540bf5b55f5f9f6b4ce2cf97fd',
        10 => '6e91c040a26b1139122e82d3169604ed',
    ];
}
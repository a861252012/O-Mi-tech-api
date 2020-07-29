<?php
/**
 * 財富等級 常數
 * @date 2020-06-12
 */

namespace App\Constants;

class LvRich
{
    const LIST = [
        33 => 350000000,
        32 => 274143000,
        31 => 199143000,
        30 => 144143000,
        29 => 99143000,
        28 => 64143000,
        27 => 39143000,
        26 => 29143000,
        25 => 20143000,
        24 => 14143000,
        23 => 10143000,
        22 => 7143000,
        21 => 5143000,
        20 => 3343000,
        19 => 2343000,
        18 => 1743000,
        17 => 1293000,
        16 => 993000,
        15 => 793000,
        14 => 633000,
        13 => 493000,
        12 => 373000,
        11 => 273000,
        10 => 183000,
        9  => 113000,
        8  => 63000,
        7  => 33000,
        6  => 18000,
        5  => 10000,
        4  => 5000,
        3  => 2000,
        2  => 500,
        1  => 0
    ];

    public static function calcul($exp)
    {
//        foreach (self::LIST as $k => $v) {
//            if ($exp >= $v) {
//                $newLevel = $k;
//                break;
//            }
//
//            continue;
//        }
//
//        return $newLevel;

        if (in_array($exp, self::LIST)) {
            return array_search($exp, self::LIST);
        }

        $arr = array_values(self::LIST);

        $arr[] = $exp;
        sort($arr);
        return array_search($exp, $arr);
    }
}
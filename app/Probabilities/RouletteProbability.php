<?php
/**
 * 輪盤遊戲 機率
 * @author Weine
 * @date 2020-9-14
 */

namespace App\Probabilities;


use App\Services\SiteAttrService;

class RouletteProbability
{
    public function calculate($thresHold)
    {
        return mt_rand(1, $thresHold) ?: 0;
    }
}
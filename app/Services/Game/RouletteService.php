<?php

namespace App\Services\Game;

use App\Services\SiteAttrService;

class RouletteService
{
    public function play($cnt = 1)
    {
        $siteAttrService = resolve(SiteAttrService::class);
        $rouletteItems = $siteAttrService->get('roulette_items');

        // build item thresHold
        $items = [];
        $thresHold = 0;
        foreach ($rouletteItems as $item) {
            $thresHold += (int)($item['rate'] * 100);
            $item['thresHold'] = $thresHold;
            $items[] = $item;
        }

        // calculate each result
        $results = [];
        for ($i = 0; $i < $cnt; ++$i) {
            $randomResult = mt_rand(1, $thresHold);
            foreach ($items as $item) {
                if ($randomResult <= $item['thresHold']) {
                    $results[] = [
                        'type' => $item['type'],
                        'amount' => $item['amount'],
                        'broadcast' => $item['broadcasttype'] ? 1 : 0,
                    ];
                    break;
                }
            }
        }

        return $results;
    }
}
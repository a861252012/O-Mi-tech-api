<?php
/**
 * 輪盤遊戲 服務
 * @author Weine
 * @date 2020-9-11
 */

namespace App\Services\Roulette;


use App\Facades\SiteSer;
use App\Probabilities\RouletteProbability;
use App\Services\SiteAttrService;

class RouletteService
{
    protected $siteAttrService;
    protected $rouletteProbability;

    public function __construct(
        SiteAttrService $siteAttrService,
        RouletteProbability $rouletteProbability
    ) {
        $this->siteAttrService = $siteAttrService;
        $this->rouletteProbability = $rouletteProbability;
    }

    public function getSetting()
    {
        $items = json_decode($this->siteAttrService->get('roulette_items'));

        return [
            'switch' => (int)SiteSer::globalSiteConfig('roulette_switch'),
            'cost'   => (int)$this->siteAttrService->get('roulette_cost'),
            'items'  => collect($items)->map(function ($value, $key) {
                unset($value->rate);
                unset($value->broadcast);
                return $value;
            }),
        ];
    }

    public function play($cnt = 1)
    {
        $rouletteItems = json_decode($this->siteAttrService->get('roulette_items'), true);

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
            $randomResult = $this->rouletteProbability->calculate($thresHold);
            foreach ($items as $item) {
                if ($randomResult <= $item['thresHold']) {
                    $results[] = [
                        'type'      => $item['type'],
                        'amount'    => $item['amount'],
                        'broadcast' => $item['broadcasttype'] ? 1 : 0,
                    ];
                    break;
                }
            }
        }

        return $results;
    }
}
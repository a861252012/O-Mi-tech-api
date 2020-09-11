<?php
/**
 * 輪盤遊戲 服務
 * @author Weine
 * @date 2020-9-11
 */

namespace App\Services;


class RouletteService
{
    protected $siteAttrService;

    public function __construct(SiteAttrService  $siteAttrService)
    {
        $this->siteAttrService = $siteAttrService;
    }

    public function getSetting()
    {
        $cost = (int) $this->siteAttrService->get('roulette_cost');
        $items = $this->siteAttrService->get('roulette_items');

        return [
            'cost' => $cost ?? 0,
            'items' => $items ?? [],
        ];
    }
}
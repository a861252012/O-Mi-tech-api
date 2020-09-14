<?php

namespace App\Services\Game;

use App\Services\SiteAttrService;
use App\Repositories\RouletteRepository;

class RouletteService
{
    protected $siteAttrService;
    protected $rouletteRepository;

    public function __construct(
        SiteAttrService $siteAttrService,
        RouletteRepository $rouletteRepository
    ) {
        $this->siteAttrService = $siteAttrService;
        $this->rouletteRepository = $rouletteRepository;
    }

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

    public function getSetting()
    {
        $cost = (int) $this->siteAttrService->get('roulette_cost');
        $items = $this->siteAttrService->get('roulette_items');

        return [
            'cost' => $cost ?? 0,
            'items' => $items ?? [],
        ];
    }

    public function getHistory($uid, $amount, $startTime, $endTime)
    {
        return $this->rouletteRepository->getHistory($uid, $amount, $startTime, $endTime);
    }
}
<?php
/**
 * 輪盤遊戲 服務
 * @author Weine
 * @date 2020-9-11
 */

namespace App\Services\Roulette;


use App\Events\RouletteReward;
use App\Facades\SiteSer;
use App\Probabilities\RouletteProbability;
use App\Repositories\RouletteHistoryRepository;
use App\Services\SiteAttrService;
use App\Services\UserAttrService;
use Illuminate\Support\Facades\Auth;

class RouletteService
{
    protected $siteAttrService;
    protected $userAttrService;
    protected $rouletteProbability;
    protected $rouletteHistoryRepository;

    public function __construct(
        SiteAttrService $siteAttrService,
        UserAttrService $userAttrService,
        RouletteProbability $rouletteProbability,
        RouletteHistoryRepository $rouletteHistoryRepository
    ) {
        $this->siteAttrService = $siteAttrService;
        $this->userAttrService = $userAttrService;
        $this->rouletteProbability = $rouletteProbability;
        $this->rouletteHistoryRepository = $rouletteHistoryRepository;
    }

    public function status()
    {
        return (int)SiteSer::globalSiteConfig('roulette_switch');
    }

    public function cost()
    {
        return (int)$this->siteAttrService->get('roulette_cost');
    }

    public function items()
    {
        $items = json_decode($this->siteAttrService->get('roulette_items'));

        return collect($items)->map(function ($value, $key) {
            unset($value->rate);
            unset($value->broadcast);
            return $value;
        });
    }

    public function freeTicket()
    {
        return (int)$this->userAttrService->get(Auth::id(), 'roulette_tickets');
    }

    public function play($cnt = 1)
    {
        // 抽獎資格判斷

        // 扣鑽或扣票

        // 中獎邏輯
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

        //新增個人中獎紀錄
        $reward = [];

        //送禮 加鑽 加經驗
        //更新日排行
        //更新中獎跑道
        event(new RouletteReward(Auth::user(), $reward));

        //發送廣播訊息(redis)

        return $results;
    }

    public function getHistory($uid, $amount, $startTime, $endTime)
    {
        return $this->rouletteHistoryRepository->getHistory($uid, $amount, $startTime, $endTime);
    }
}
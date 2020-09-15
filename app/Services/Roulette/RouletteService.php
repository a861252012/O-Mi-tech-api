<?php
/**
 * 輪盤遊戲 服務
 * @author Weine
 * @date 2020-9-11
 */

namespace App\Services\Roulette;


use App\Events\RouletteReward;
use App\Facades\SiteSer;
use App\Facades\UserSer;
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

    // 此輪消費鑽石數
    protected $consumePoints;

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

    public function status() : int
    {
        return (int)SiteSer::globalSiteConfig('roulette_switch');
    }

    public function cost() : int
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

    public function freeTicket() : int
    {
        return (int)$this->userAttrService->get(Auth::id(), 'roulette_tickets');
    }

    public function checkPlay($cnt = 1) : bool
    {
        $freeTicket = $this->freeTicket();
        if ($freeTicket) {
            return $freeTicket < $cnt;
        }

        $this->consumePoints = $this->cost() * $cnt;
        return Auth::user()->points < $this->cost();
    }

    public function play($rid, $cnt = 1)
    {
        $user = Auth::user();
        $freeTicket = $this->freeTicket();

        // 取得所有獎項配置
        $rouletteItems = json_decode($this->siteAttrService->get('roulette_items'), true);

        // ---中獎邏輯---
        // build item thresHold
        $thresHold = 0;
        $items = collect($rouletteItems)->map(function ($value, $key) use (&$thresHold) {
            $thresHold += $value['rate'] * 100;
            $value['thresHold'] = $thresHold;
            return $value;
        })->all();

//        $items = [];
//        foreach ($rouletteItems as $item) {
//            $thresHold += (int)($item['rate'] * 100);
//            $item['thresHold'] = $thresHold;
//            $items[] = $item;
//        }
//
//        dd($items);

        $results = []; //回傳資料
        $insertData = []; //紀錄資料
        $mtimeStr = explode(' ', microtime());

        // 產生批號
        $groupId = $mtimeStr[1] . substr($mtimeStr[0],2, -2);

        // 計算中獎獎項
        for ($i = 0; $i < $cnt; ++$i) {
            $randomResult = $this->rouletteProbability->calculate($thresHold);
            foreach ($items as $item) {
                if ($randomResult <= $item['thresHold']) {
                    $results[] = [
                        'type'      => $item['type'],
                        'amount'    => $item['amount'],
                        'broadcast' => $item['broadcast'] ? 1 : 0,
                    ];

                    $insertData[] = [
                        'type'     => $item['type'],
                        'amount'   => $item['amount'],
                        'is_free'  => $freeTicket ? 1 : 0,
                        'rid'      => $rid,
                        'uid'      => $user->uid,
                        'group_id' => $groupId,
                    ];
                    break;
                }
            }
        }
        // ---中獎邏輯 END---

        // 新增個人中獎紀錄
        $this->rouletteHistoryRepository->insertData($insertData);

        // 先扣免費票，如無則扣鑽
//        if ($freeTicket) {
//            $this->userAttrService->set($user->uid, 'roulette_tickets', $freeTicket - 1);
//        } else {
//            UserSer::updateUserInfo($user->uid, ['points' => $user->points - $this->consumePoints]);
//        }

        //送禮 加鑽 加經驗
        //更新日排行
        //更新中獎跑道
        //發送廣播訊息(redis)
//        event(new RouletteReward($user, $results));

        return $results;
    }

    public function getHistory($uid, $amount, $startTime, $endTime)
    {
        return $this->rouletteHistoryRepository->getHistory($uid, $amount, $startTime, $endTime);
    }
}
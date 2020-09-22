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
use Illuminate\Support\Facades\DB;

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
            $value->icon = SiteSer::config('img_host') . '/' . $value->icon . '.png';
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
        $freeTicket = $this->freeTicket(); // 取得用戶目前免費票卷
        $cost = $this->cost(); // 取得目前單一次花費鑽石

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
                        'cost'     => $freeTicket ? 0 : $cost,
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
        if ($freeTicket) {
            $this->userAttrService->set($user->uid, 'roulette_tickets', $freeTicket - 1);
        } else {
            UserSer::updateUserInfo($user->uid, ['points' => $user->points - $this->consumePoints]);
        }

        // 發放獎勵
        // 更新日排行
        // 更新中獎跑道
        // 發送廣播訊息(redis)
        event(new RouletteReward($user->refresh(), $results, $rid));

        return $results;
    }

    public function getHistory($origin, $uid, $amount, $startTime, $endTime)
    {
        $start = date('Y-m-d 00:00:00', $startTime ? strtotime($startTime) : strtotime('-1 week'));
        $end = date('Y-m-d 23:59:59', $endTime ? strtotime($endTime) : mktime(date(23), date(59), date(59)));

        if (strtotime($start) > strtotime($end)) {
            return [];
        }

        //如為PC,則不帶起訖時間
        if ($origin == 11) {
            $start = null;
            $end = null;
        }

        $list = $this->rouletteHistoryRepository->getHistory($uid, $amount, $start, $end)->toArray();
        foreach ($list['data'] as $k => &$v) {
            $v['name'] = __('messages.RouletteItem.type.' . $v['type']);
        }

        return $list;
    }

    //取得前十跑道用戶資訊
    public function getLastTenInfo()
    {
        $data = resolve('redis')->ZREVRANGE('zroulette_news', 0, 9);

        foreach ($data as $k => $v) {
            $data[$k] = json_decode($v);
        }

        return $data;
    }
}

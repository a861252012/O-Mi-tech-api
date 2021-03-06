<?php
/**
 * 輪盤遊戲 服務
 * @author Weine
 * @date 2020-9-11
 */

namespace App\Services\Roulette;

use App\Constants\RouletteItem;
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

    public function status(): int
    {
        return (int)SiteSer::globalSiteConfig('roulette_switch');
    }

    public function cost(): int
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

    public function freeTicket(): int
    {
        return (int)$this->userAttrService->get(Auth::id(), 'roulette_tickets');
    }

    public function checkPlay($cnt = 1): bool
    {
        $freeTicket = $this->freeTicket();
        if ($freeTicket >= $cnt) {
            return false;
        }

        $this->consumePoints = $this->cost() * ($cnt - $freeTicket);
        return Auth::user()->points < $this->consumePoints;
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
            $thresHold += $value['rate'] * 10000;
            $value['thresHold'] = $thresHold;
            return $value;
        })->all();

        $results = []; //回傳資料
        $insertData = []; //紀錄資料
        $mtimeStr = explode(' ', microtime());

        // 產生批號
        $groupId = $mtimeStr[1] . substr($mtimeStr[0], 2, -2);

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

                    $isFree = (($freeTicket - ($i + 1)) < 0) ? 0 : 1;
                    $insertData[] = [
                        'type'     => $item['type'],
                        'amount'   => $item['amount'],
                        'cost'     => $isFree ? 0 : $cost,
                        'is_free'  => $isFree,
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

        // 扣免費票
        if ($freeTicket) {
            $freeTicket -= $cnt;
            if ($freeTicket < 0) {
                $freeTicket = 0;
            }

            $this->userAttrService->set($user->uid, 'roulette_tickets', $freeTicket);
        }

        // 扣鑽
        if ($this->consumePoints) {
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
        $uxStart = strtotime($startTime);
        $uxEnd = strtotime($endTime);

        /* 開始時間處理 */
        if (empty($uxStart)) {
            if (empty($origin) || $origin == 11) {
                $start = null;
            } else {
                $start = date('Y-m-d 00:00:00', strtotime('-1 week'));
            }
        } else {
            $start = date('Y-m-d 00:00:00', $uxStart);
        }

        /* 結束時間處理 */
        if (empty($uxEnd)) {
            if (empty($origin) || $origin == 11) {
                $end = null;
            } else {
                $end = date('Y-m-d 23:59:59');
            }
        } else {
            $end = date('Y-m-d 23:59:59', $uxEnd);
        }

        /* 驗證時間區間 */
        if ($uxStart > $uxEnd) {
            return false;
        }

        $imgHost = SiteSer::siteConfig('img_host', SiteSer::siteId());
        $list = $this->rouletteHistoryRepository->getHistory($uid, $amount, $start, $end)->toArray();

        foreach ($list['data'] as $k => &$v) {
            $v['name'] = __('messages.RouletteItem.type.' . $v['type']);
            $v['icon'] = $imgHost . '/' . RouletteItem::ITEM_ICON[$v['type']] . '.png';
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

<?php
/**
 * 守護功能 服務
 * @author Weine
 * @date 2020/02/15
 */

namespace App\Services;


use App\Http\Resources\Guardian\GuardianMyInfoResource;
use App\Http\Resources\Guardian\GuardianSettingResource;
use App\Repositories\GuardianRepository;
use App\Repositories\GuardianSettingRepository;
use App\Repositories\UsersRepository;
use Illuminate\Support\Facades\Redis;

class GuardianService
{
    protected $guardianSettingRepository;
    protected $guardianRepository;
    protected $usersRepository;

    public function __construct(
        GuardianSettingRepository $guardianSettingRepository,
        GuardianRepository $guardianRepository,
        UsersRepository $usersRepository
    ) {
        $this->guardianSettingRepository = $guardianSettingRepository;
        $this->guardianRepository = $guardianRepository;
        $this->usersRepository = $usersRepository;
    }

    /* 取得設定 */
    public function getSetting()
    {
        return GuardianSettingResource::collection($this->guardianSettingRepository->getAll());
    }

    /* 取得我的守護 */
    public function getMyInfo()
    {
//        $result = $this->guardianRepository->getMy(auth()->id());
//        $guardianPermission = $this->guardianSettingRepository->getOne($result->guard_id);
//        $result['guardian_permission'] = collect($guardianPermission)->except(['id', 'name', 'activate', 'renewal', 'created_at', 'updated_at']);
//
//        return $result;

        return new GuardianMyInfoResource($this->usersRepository->getUserById(auth()->id()));
    }

    //取得user最新的財富等級
    public function getRichLv($user_exp)
    {
        $richLv = array(
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
        );

        foreach ($richLv as $k => $v) {
            if ($user_exp >= $v) {
                $new_level = $k;
                break;
            }
            continue;
        }

        return $new_level;
    }


    //取得主播最新的等級
    public function getAnchorLevel($anchor_exp)
    {
        $levelExp = array(
            30 => 93850000,
            29 => 79650000,
            28 => 67050000,
            27 => 55950000,
            26 => 46250000,
            25 => 37850000,
            24 => 30650000,
            23 => 24550000,
            22 => 19450000,
            21 => 15250000,
            20 => 11850000,
            19 => 9150000,
            18 => 7050000,
            17 => 5450000,
            16 => 4250000,
            15 => 3350000,
            14 => 2650000,
            13 => 2050000,
            12 => 1550000,
            11 => 1150000,
            10 => 850000,
            9  => 600000,
            8  => 400000,
            7  => 250000,
            6  => 150000,
            5  => 100000,
            4  => 60000,
            3  => 30000,
            2  => 10000,
            1  => 0
        );

        foreach ($levelExp as $k => $v) {
            if ($anchor_exp >= $v) {
                $new_exp = $k;
                break;
            }
            continue;
        }

        return $new_exp;
    }

    /* 計算進直播間價格 */
    public function calculRoomSale($price, $salePercent)
    {
        return (int) round(((100 - $salePercent)/100) * $price);
    }

    /* 更新user redis資訊 */
    public function updateUserRedis($user, $u = array())
    {

        if (!Redis::exists('huser_info:' . $user->uid)) {
            Redis::hMSet('huser_info:' . $user->uid, $user->toArray());
            Redis::expire('huser_info:' . $user->uid, 216000);
        }

        Redis::hMSet('huser_info:' . $user->uid, $u);
    }

    /* 更新user redis資訊 */
    public function updateAnchorRedis($rid, $a = array())
    {
        if (!empty($a)) {
            Redis::hMSet('huser_info:' . $rid, $a);
            Redis::hSet('hvediosKtv:' . $rid, 'cur_exp', $a['exp']);
        }
    }

    /* 刪除user redis特權 */
    public function delUserPrivilege($uid, $currentYM, $currentYMD)
    {
        Redis::del('sguardian_chat_interval:' . $uid);
        Redis::del('sguardian_rename_' . $currentYM . ':' . $uid);
        Redis::del('sguardian_feiping_' . $currentYM . ':' . $uid);
        Redis::del('sguardian_forbid_' . $currentYMD . ':' . $uid);
        Redis::del('sguardian_kick_' . $currentYMD . ':' . $uid);
    }

    /* 更新個人排行榜資訊 */
    public function updateUserRank($siteId, $finalPrice, $uid, $currentYM)
    {
        Redis::zIncrBy('zrank_rich_day:' . $siteId, $finalPrice, $uid);
        Redis::zIncrBy('zrank_rich_week:' . $siteId, $finalPrice, $uid);
        Redis::zIncrBy('zrank_rich_month:' . $currentYM . ':' . $siteId, $finalPrice, $uid);
        Redis::zIncrBy('zrank_rich_history:' . $siteId, $finalPrice, $uid);
    }

    //更新房間排行榜資訊
    public function updateRoomRank($rid, $uid, $finalPrice, $diffWithNextMon, $currentYMD)
    {
        //直播間-週貢獻榜，zrange_gift_week:主播id，此key若一開始不存在則新增後需要設定ttl為現在到下週0點剩餘的時間
        $checkWeekGift = Redis::exists('zrange_gift_week:' . $rid);
        if (!$checkWeekGift) {
            Redis::zIncrBy('zrange_gift_week:' . $rid, $finalPrice, $uid);
            Redis::expire('zrange_gift_week:' . $rid, $diffWithNextMon);
        } else {
            Redis::zIncrBy('zrange_gift_week:' . $rid, $finalPrice, $uid);
        }

        Redis::zIncrBy('zrank_pop_day', $finalPrice, $rid);
        Redis::zIncrBy('zrank_pop_week', $finalPrice, $rid);
        Redis::zIncrBy('zrank_pop_month:' . $currentYMD, $finalPrice, $rid);
        Redis::zIncrBy('zrank_pop_history', $finalPrice, $rid);
        Redis::zIncrBy('zrank_order_today:' . $rid, $finalPrice, $uid);
        Redis::zIncrBy('zrange_gift_history:' . $rid, $finalPrice, $uid);
    }

    //(直播間內開通才要做)，新增點亮置頂次數
    public function toTheToppest($siteId, $rid, $finalPrice, $diffWithTomorrow)
    {
        $checkTopThreshold = Redis::hExists('hsite_config:' . $siteId, 'top_threshold');

        if (!$checkTopThreshold) {
            Redis::expire('huser_recommend_anchor:' . $rid, $diffWithTomorrow);
        }

        $topThreshold = Redis::hget('hsite_config:' . $siteId, 'top_threshold');

        if ($finalPrice >= $topThreshold) {
            Redis::HINCRBY('hsite_config:' . $siteId, 'huser_recommend_anchor:' . $rid, 1);
        }

    }

    //判斷是否新增白名單或是累積單場消費紀錄
    public function WhiteList($uid, $rid, $finalPrice)
    {
        //如消費金額大於一對多價格,則新增白名單
        if (Redis::exists('hroom_whitelist_key:' . $uid)) {
            $whiteListKey = Redis::SMEMBERS('hroom_whitelist_key:' . $uid);

            $oneToMorePrice = Redis::hget('hroom_whitelist:' . $uid . ':' . $whiteListKey[0], 'points');

            if ($finalPrice >= $oneToMorePrice) {
                $whiteList = Redis::hget('hroom_whitelist:' . $uid . ':' . $whiteListKey[0], 'uids');
                Redis::hSet('hroom_whitelist:' . $rid . ':' . $whiteListKey[0], 'uids',
                    $whiteList . ',' . $uid);
                Redis::HINCRBY('hroom_whitelist:' . $rid . ':' . $whiteListKey[0], 'nums', 1);
            }
        }

        //累積單場消費紀錄
        if (Redis::exists('one2many_statistic:' . $rid)) {
            Redis::hIncrBy('one2many_statistic:' . $rid, $uid, $finalPrice);
        }
    }

    //新增守護在線列表redis key
    public function userOnlineList($rid, $uid)
    {
        $checkUserOnline = Redis::hExists('hguardian_online:' . $rid, $uid);

        if (!$checkUserOnline) {
            Redis::hSet('hguardian_online:' . $rid, $uid, $uid);
        }
    }

    //通知java直播間內開通
    public function pubToJava($guardId, $rid, $uid)
    {
        $activateNotify = Redis::hGet('hguardian_info:' . $guardId, 'activate_notify');

        if ($activateNotify) {
            Redis::publish('guardian_broadcast_info',
                json_encode([
                    'rid'     => (int)$rid,
                    'uid'     => (int)$uid,
                    'guardId' => (int)$guardId
                ])
            );
        }
    }

}
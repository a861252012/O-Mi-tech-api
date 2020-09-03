<?php
/**
 * 守護功能 服務
 * @author Weine
 * @date 2020/02/15
 */

namespace App\Services;


use App\Entities\Guardian;
use App\Entities\SiteConfigs;
use App\Entities\UserHost;
use App\Facades\SiteSer;
use App\Http\Resources\Guardian\GuardianMyInfoResource;
use App\Http\Resources\Guardian\GuardianSettingResource;
use App\Models\Users;
use App\Repositories\GuardianRepository;
use App\Repositories\GuardianSettingRepository;
use App\Repositories\UserHostRepository;
use App\Repositories\UsersRepository;
use App\Models\MallList;
use App\Services\Message\MessageService;
use Carbon\Carbon;
use DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Services\User\UserService;

class GuardianService
{
    const DEFAULT_IMG = array(
        1 => 'c62e026d074d331bb98be8a63b4f5303',
        2 => '7628eb77abb956b94d5fdf75427c1b1b',
        3 => '553a1e24744251a208a2fd1afd6e6ba0'
    );

    const VALID_DAY_ARR = array(1 => 30, 2 => 90, 3 => 365);
    const GUARDIAN_GIFT_ID = array(1 => 700004, 2 => 700005, 3 => 700006);
    const PAY_TYPE_CH = array(1 => '开通成功', 2 => '续费成功');
    const PAY_TYPE_EN = array(1 => 'activate', 2 => 'renewal');

    protected $guardianSettingRepository;
    protected $guardianRepository;
    protected $usersRepository;
    protected $userService;
    protected $messageService;
    protected $userHostRepository;

    public function __construct(
        GuardianSettingRepository $guardianSettingRepository,
        GuardianRepository $guardianRepository,
        UsersRepository $usersRepository,
        UserService $userService,
        MessageService $messageService,
        UserHostRepository $userHostRepository
    ) {
        $this->guardianSettingRepository = $guardianSettingRepository;
        $this->guardianRepository = $guardianRepository;
        $this->usersRepository = $usersRepository;
        $this->userService = $userService;
        $this->messageService = $messageService;
        $this->userHostRepository = $userHostRepository;
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
    public function getRichLv($userExp)
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
            if ($userExp >= $v) {
                $newLevel = $k;
                break;
            }
            continue;
        }

        return $newLevel;
    }

    //取得主播最新的等級
    public function getAnchorLevel($anchorExp)
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
            if ($anchorExp >= $v) {
                $newExp = $k;
                break;
            }
            continue;
        }

        return $newExp;
    }

    /* 計算進直播間價格 */
    public function calculRoomSale($price, $salePercent)
    {
        return (int)round(((100 - $salePercent) / 100) * $price);
    }

    /* 檢核流程  購買守護流程 */
    public function purchaseProcess($user, $payType, $daysType, $guardId, $rid = 0)
    {
        if (!Redis::hExists('hroom_ids', $rid)) {
            $rid = 0;
        }

        $currentDateTime = Carbon::now()->copy()->toDateTimeString();

        //取得守護設定價格
        $priceKey = self::PAY_TYPE_EN[$payType] . '_' . self::VALID_DAY_ARR[$daysType];

        $price = $this->getGuardianPrice($priceKey, $guardId);

        //計算該開通/續約所需的最終價格
        if (!$price['sale']) {
            $price['final'] = $price['origin'];
        } else {
            $price['final'] = $price['sale'];
        }

        //檢核方案是否有開啟
        if (!$price['final']) {
            return [
                'status' => 101,
                'msg' => __('messages.Guardian.buy.class_not_active', ['day' => self::VALID_DAY_ARR[$daysType]]),
            ];
        }

        //檢核用戶鑽石是否足夠，不足返回資訊
        if (!($user->points >= $price['final'])) {
            return ['status' => 102, 'msg' => __('messages.Guardian.buy.user_point_not_enough')];
        }

        //檢核續費還是新開通，是否有符合規定
        if ($user->guard_end >= Carbon::now()) {//如有開通且未過期
            if ($user->guard_id > $guardId) {
                return ['status' => 103, 'msg' => __('messages.Guardian.buy.level_is_high')];
            } else {
                if ($user->guard_id == $guardId && $payType != 2) {
                    return ['status' => 104, 'msg' => __('messages.Guardian.buy.only_renewal')];
                } else {
                    if ($user->guard_id < $guardId && $payType != 1) {
                        //(原守護等級 < 開通/續約等級 && 不是新開通)
                        return ['status' => 105, 'msg' => __('messages.Guardian.buy.only_active')];
                    }
                }
            }
        } else {
            if ($payType == 2) {
                return ['status' => 105, 'msg' => __('messages.Guardian.buy.only_active')];
            }
        }

        //計算守護到期日
        if (!$user->guard_end) {
            $guardEndTime = Carbon::now()->copy()->addDays(self::VALID_DAY_ARR[$daysType]);
        } else {
            if ($user->guard_end >= $currentDateTime) {
                if ($payType == 1 && $guardId > $user->guard_id) {
                    $guardEndTime = Carbon::now()->copy()->addDays(self::VALID_DAY_ARR[$daysType]);
                } else {
                    $guardEndTime = Carbon::parse($user->guard_end)->copy()->subDay()->addDays(self::VALID_DAY_ARR[$daysType]);
                }
            } else {
                $guardEndTime = Carbon::now()->copy()->addDays(self::VALID_DAY_ARR[$daysType]);
            }
        }

        /* DB PART START */
        DB::beginTransaction();

        //新增守護記錄
        $guardianRecordArr = array(
            'uid'         => $user->uid,
            'rid'         => $rid,
            'pay_date'    => Carbon::now()->copy()->toDateString(),
            'valid_day'   => self::VALID_DAY_ARR[$daysType],
            'price'       => $price['origin'],
            'sale'        => $price['sale'],
            'pay'         => $price['final'],
            'expire_date' => $guardEndTime->copy()->addDay()->toDateString(),
            'guard_id'    => $guardId,
            'pay_type'    => $payType,
            'created_at'  => $currentDateTime,
            'updated_at'  => $currentDateTime
        );

        $insertGuardian = $this->guardianRepository->insertGuardianRecord($guardianRecordArr);

        if (!$insertGuardian) {
            Log::error('新增守護記錄錯誤');
            DB::rollBack();
            return false;
        }

        //取得用戶守護大頭貼，房間內就撈主播海報，房間外用官方固定的守護圖
        $headimg = $this->guardianRepository->getHeadImg($rid);

        if (!$headimg) {
            $headimg = self::DEFAULT_IMG[$guardId];
        }

        //異動用戶資料
        $u['points'] = ($user->points - $price['final']); // 扣除鑽石後的餘額
        $u['rich'] = ($user->rich + $price['final']); // 增加用戶財富經驗值
        $u['lv_rich'] = $this->getRichLv($u['rich']); // 計算用戶財富新等級
        $u['guard_id'] = $guardId; // 開通守護等級
        $u['guard_end'] = $guardEndTime->copy()->addDay()->toDateString(); // 守護到期日
        $u['headimg'] = $headimg . '.jpg';
        $u['update_at'] = $currentDateTime;

        $updateUser = $this->userService->updateUserInfo($user->uid, $u);

        if (!$updateUser) {
            Log::error('異動用戶資料錯誤');
            DB::rollBack();
            return false;
        }

        //異動主播資料
        if ($rid) {
            $anchorExp = $this->userService->getUserByUid($rid)->exp;

            $a['exp'] = ($anchorExp + $price['final']); // 增加主播經驗值
            $a['lv_exp'] = $this->getAnchorLevel($a['exp']); // 計算主播新等級
            $a['update_at'] = $currentDateTime;

            $updateAnchor = $this->userService->updateUserInfo($rid, $a);

            if (!$updateAnchor) {
                Log::error('異動主播資料錯誤');
                DB::rollBack();
                return false;
            }
        }

        //新增送禮紀錄
        $sendGiftRecord = array(
            'send_uid'   => $user->uid,
            'rec_uid'    => $rid,
            'gid'        => self::GUARDIAN_GIFT_ID[$guardId],
            'gnum'       => 1,
            'rid'        => $rid,
            'points'     => $price['final'],
            'rate'       => '50',
            'origin'     => $user->origin,
            'site_id'    => SiteSer::siteId(),
            'guard_id'   => $user->guard_id,
            'guard_days' => self::VALID_DAY_ARR[$daysType],
            'created'    => $currentDateTime
        );

        $insertGiftRecord = $this->guardianRepository->insertGiftRecord($sendGiftRecord);

        if (!$insertGiftRecord) {
            Log::error('新增送禮紀錄錯誤');
            DB::rollBack();
            return false;
        }
        /* 守护开通成功提醒 */
        $guardName = $this->getSetting()->pluck('name', 'id');

        $expireMsgDate = $guardEndTime->copy()->toDateString();

        $message = [
            'category'  => 1,
            'mail_type' => 3,
            'rec_uid'   => $user->uid,
            'content'   => '守护' . self::PAY_TYPE_CH[$payType] . '提醒：您已成功开通 ' . $guardName[$guardId] . ' ，到期日：' . $expireMsgDate
        ];

        $sendMsgToUser = $this->messageService->sendSystemToUsersMessage($message);

        if (!$sendMsgToUser) {
            Log::error('守护开通成功提醒錯誤');
            DB::rollBack();
            return false;
        }

        DB::commit();
        /* DB PART END */

        //Redis Part

        //如果用戶守護不是第一次開通，且開通等級比上一次大,則重製用戶的守護特權
        if ($guardId > $user->guard_id && $payType == 1) {
            Redis::del('sguardian_chat_interval:' . $user->uid);
            Redis::del('sguardian_rename_' . Carbon::now()->copy()->format('Ym') . ':' . $user->uid);
            Redis::del('sguardian_feiping_' . Carbon::now()->copy()->format('Ym') . ':' . $user->uid);
            Redis::del('sguardian_forbid_' . Carbon::now()->copy()->format('Ymd') . ':' . $user->uid);
            Redis::del('sguardian_kick_' . Carbon::now()->copy()->format('Ymd') . ':' . $user->uid);
        }

        //更新個人排行榜資訊
        Redis::zIncrBy('zrank_rich_day:' . SiteSer::siteId(), $price['final'], $user->uid);
        Redis::zIncrBy('zrank_rich_week:' . SiteSer::siteId(), $price['final'], $user->uid);
        Redis::zIncrBy('zrank_rich_month:' . Carbon::now()->copy()->format('Ym') . ':' . SiteSer::siteId(),
            $price['final'], $user->uid);
        Redis::zIncrBy('zrank_rich_history:' . SiteSer::siteId(), $price['final'], $user->uid);

        if ($rid) {
            //異動主播開播資料
            Redis::hSet('hvediosKtv:' . $rid, 'cur_exp', $a['exp']);

            $diffWithNextMon = Carbon::now()->copy()->diffInSeconds(Carbon::now()->copy()->addDays(7)->startOfWeek());//距離下週一的秒數
            $diffWithTomorrow = Carbon::now()->copy()->diffInSeconds(Carbon::now()->copy()->tomorrow());

            //判斷是否新增白名單或是累積單場消費紀錄,如消費金額大於一對多價格,則新增白名單
            if (Redis::exists('hroom_whitelist_key:' . $rid)) {
                $whiteListKey = Redis::SMEMBERS('hroom_whitelist_key:' . $rid);

                $oneToMorePrice = Redis::hget('hroom_whitelist:' . $rid . ':' . $whiteListKey[0], 'points');

                if ($price['final'] >= $oneToMorePrice) {
                    $whiteList = Redis::hget('hroom_whitelist:' . $rid . ':' . $whiteListKey[0], 'uids');

                    Redis::hSet('hroom_whitelist:' . $rid . ':' . $whiteListKey[0], 'uids',
                        $whiteList . ',' . $user->uid);
                    Redis::HINCRBY('hroom_whitelist:' . $rid . ':' . $whiteListKey[0], 'nums', 1);
                }
            }

            //累積單場消費紀錄
            Redis::hIncrBy('one2many_statistic:' . $rid, $user->uid, $price['final']);

            //(直播間內開通才要做)，新增點亮置頂次數
            $checkTopThreshold = Redis::hExists('hsite_config:' . SiteSer::siteId(), 'top_threshold');

            if (!$checkTopThreshold) {
                Redis::expire('huser_recommend_anchor:' . $rid, $diffWithTomorrow);
            }

            $topThreshold = Redis::hget('hsite_config:' . SiteSer::siteId(), 'top_threshold');

            if ($price['final'] >= $topThreshold) {
                Redis::HINCRBY('hsite_config:' . SiteSer::siteId(), 'huser_recommend_anchor:' . $rid, 1);
            }

            //更新房間排行榜資訊  直播間-週貢獻榜，zrange_gift_week:主播id，此key若一開始不存在則新增後需要設定ttl為現在到下週0點剩餘的時間
            $checkWeekGift = Redis::exists('zrange_gift_week:' . $rid);

            if (!$checkWeekGift) {
                Redis::zIncrBy('zrange_gift_week:' . $rid, $price['final'], $user->uid);
                Redis::expire('zrange_gift_week:' . $rid, $diffWithNextMon);
            } else {
                Redis::zIncrBy('zrange_gift_week:' . $rid, $price['final'], $user->uid);
            }

            Redis::zIncrBy('zrank_pop_day', $price['final'], $rid);
            Redis::zIncrBy('zrank_pop_week', $price['final'], $rid);
            Redis::zIncrBy('zrank_pop_month:' . Carbon::now()->copy()->format('Ym'), $price['final'], $rid);
            Redis::zIncrBy('zrank_pop_history', $price['final'], $rid);
            Redis::zIncrBy('zrank_order_today:' . $rid, $price['final'], $user->uid);
            Redis::zIncrBy('zrange_gift_history:' . $rid, $price['final'], $user->uid);

            //新增守護在線列表對應的redis key
            $checkUserOnline = Redis::hExists('hguardian_online:' . $rid, $user->uid);

            if (!$checkUserOnline) {
                Redis::hSet('hguardian_online:' . $rid, $user->uid, $user->uid);
            }

            //通知java直播間內開通
            Redis::publish(
                'guardian_broadcast_info',
                json_encode([
                    'rid'     => (int)$rid,
                    'uid'     => (int)$user->uid,
                    'guardId' => (int)$guardId,
                    'price'   => (int)$price['final']
                ])
            );
        }

        $res['expireDate'] = $expireMsgDate;
        $res['guardianName'] = $guardName[$guardId];
        $res['payTypeName'] = self::PAY_TYPE_CH[$payType];
        $res['status'] = 200;

        return $res;
    }

    /* 取得redis守護設定價格*/
    public function getGuardianPrice($priceKey, $guardId)
    {
        $getSalePrice = Redis::hGet('hguardian_info:' . $guardId, $priceKey . '_sale');
        $getPrice = Redis::hGet('hguardian_info:' . $guardId, $priceKey);

        //計算該開通/續約所需的最終價格
        if (!$getSalePrice) {
            $finalPrice = $getPrice;
        } else {
            $finalPrice = $getSalePrice;
        }

        return $data = ['final' => $finalPrice, 'sale' => $getSalePrice, 'origin' => $getPrice];
    }

    /* 主播海報檔案處理 */
    public function coverTrans($imgCode)
    {
        return $this->userHostRepository->updateOrCreate(Auth::id(), ['cover' => $imgCode]);
    }

    /* 主播海報檔案處理 */
    public function getGuardianHistory()
    {
        $list = Auth::user()->guardian()->paginate()->toArray();

        foreach ($list['data'] as $k => $v) {
            $list['data'][$k]['expire_date'] = Carbon::parse($v['expire_date'])->copy()->subDay()->toDateString();
        }

        return $list;
    }
}
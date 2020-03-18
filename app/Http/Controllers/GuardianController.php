<?php
/**
 * 守護功能 控制器
 * @author Weine
 * @date 2020/02/15
 * @apiDefine Guardian 守護功能
 */

namespace App\Http\Controllers;

use App\Facades\SiteSer;
use App\Http\Requests\Guardian\GuardianBuy;
use App\Models\Users;
use App\Services\GuardianService;
use App\Services\Message\MessageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Auth;
use DB;


class GuardianController extends Controller
{
    protected $guardianService;

    public function __construct(GuardianService $guardianService)
    {
        $this->guardianService = $guardianService;
    }

    /**
     * @api {get} /guardian/get_setting 取得守護功能設定資訊
     * @apiGroup Guardian
     * @apiName 守護功能設定資訊
     * @apiVersion 1.0.0
     *
     * @apiError (Error Status) 999 API執行錯誤
     *
     * @apiSuccess {String} faq FAQ連結
     * @apiSuccess {Array} guard_settings 守護設定
     * @apiSuccess {Int} id 守護ID
     * @apiSuccess {String} name 守護名稱
     * @apiSuccess {Object[]} activate 開通費用設定
     * @apiSuccess {Array} activate.days 天數(30天/90天/365天)
     * @apiSuccess {Array} activate.sale 優惠價格
     * @apiSuccess {Array} activate.price 一般價格
     * @apiSuccess {Object} renewal 續費設定(內容格式與開通相同)
     * @apiSuccess {Int} activate_notify 開通通知(0:關/1:房間通知/2:全站通知)
     * @apiSuccess {Boolean} welcome_notify 進場歡迎通知(0:關/1:開)
     * @apiSuccess {Boolean} shot_border 頭像邊框(0:關/1:開)
     * @apiSuccess {Boolean} rename 改名限制(0:不可修改/1:可修改)
     * @apiSuccess {Int} rename_limit 改名限制次數
     * @apiSuccess {Boolean} feiping 專屬飛頻(0:關/1:開)
     * @apiSuccess {Int} feiping_count 贈送飛頻數
     * @apiSuccess {Boolean} chat_bg 聊天底圖(0:關/1:開)
     * @apiSuccess {Boolean} chat_limit 聊天文字限制開關(0:關/1:開)
     * @apiSuccess {Int} chat_freq_limit 聊天文字時間限制(0:不限制)
     * @apiSuccess {Int} chat_length_limit 聊天文字長度限制(0:不限制)
     * @apiSuccess {Boolean} forbid 防禁言(0:關/1:開)
     * @apiSuccess {Int} forbid_count 禁言用戶數
     * @apiSuccess {Boolean} kick 防踢人(0:關/1:開)
     * @apiSuccess {Int} kick_count 踢人數
     * @apiSuccess {Int} show_discount 看秀折扣(%)
     * @apiSuccess {Boolean} hidden 是否允許隱身(0:關/1:開)
     *
     * @apiSuccessExample {json} 成功回應
     * {
    "status": 1,
    "msg": "成功",
    "data": {
    "faq": "http:\/\/localhost\/99\/static\/faq\/guardian.html",
    "guard_settiings": [
    {
    "id": 2,
    "name": "紫色守护",
    "activate": {
    "days": [
    30,
    90,
    365
    ],
    "sale": [
    10,
    100,
    300
    ],
    "price": [
    20,
    200,
    500
    ]
    },
    "renewal": {
    "days": [
    30,
    90,
    365
    ],
    "sale": [
    10,
    100,
    300
    ],
    "price": [
    20,
    200,
    500
    ]
    },
    "activate_notify": false,
    "room_notify": false,
    "all_notify": false,
    "welcome_notify": false,
    "shot_border": false,
    "rename": false,
    "rename_limit": 0,
    "feiping": false,
    "feiping_count": 0,
    "chat_bg": false,
    "chat_limit": true,
    "chat_freq_limit": 0,
    "chat_length_limit": 0,
    "forbid": false,
    "forbid_count": 0,
    "kick": false,
    "kick_count": 0,
    "show_discount": 0,
    "hidden": false
    }
    ]
    }
    }
     */
    public function getSetting()
    {
        try {
            $data = $this->guardianService->getSetting();
            if (empty($data)) {
                $this->setStatus(0, '設定為空');
                return $this->jsonOutput();
            }

            $faq = SiteSer::config('cdn_host') . '/' . SiteSer::config('publish_version') . '/static/faq/guardian.html';

            $this->setStatus(1, '成功');
            $this->setData('faq', $faq);
            $this->setData('guard_settiings', $data);
            return $this->jsonOutput();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            $this->setStatus(999, 'api執行失敗');
            return $this->jsonOutput();
        }
    }

    /**
     * @api {get} /guardian/my_info 我的守護權限
     * @apiGroup Guardian
     * @apiName 守護功能
     * @apiVersion 1.0.0
     *
     * @apiError (Error Status) 999 API執行錯誤
     *
     * @apiSuccess {String} faq FAQ連結
     * @apiSuccess {String} guard_id 守護id
     * @apiSuccess {Int} guardian_name 守護名稱
     * @apiSuccess {Int} last_activate_date 最後開通日
     * @apiSuccess {Int} last_renewal_date 最後續費日
     * @apiSuccess {String} expire_date 到期日
     * @apiSuccess {String} hidden 隱身狀態(0:關/1:開)
     * @apiSuccess {String} renewal_count 續費次數
     * @apiSuccess {Object[]} guardian_permission 守護特權
     * @apiSuccess {String} activate_notify 開通通知
     * @apiSuccess {String} welcome_notify 歡迎通知
     * @apiSuccess {String} shot_border 頭像邊框(0:關/1:開)
     * @apiSuccess {String} rename 改名限制(0:不可修改/1:可修改)
     * @apiSuccess {String} rename_limit 改名限制次數
     * @apiSuccess {String} feiping 專屬飛頻(0:關/1:開)
     * @apiSuccess {String} feiping_count 贈送飛頻數
     * @apiSuccess {String} chat_bg 聊天底圖(0:關/1:開)
     * @apiSuccess {String} chat_limit 聊天文字限制開關(0:關/1:開)
     * @apiSuccess {String} chat_freq_limit 聊天文字時間限制(0:不限制)
     * @apiSuccess {String} chat_length_limit 聊天文字長度限制(0:不限制)
     * @apiSuccess {String} forbid 防禁言(0:關/1:開)
     * @apiSuccess {String} forbid_count 禁言用戶數
     * @apiSuccess {String} kick 防踢人(0:關/1:開)
     * @apiSuccess {String} kick_count 踢人數
     * @apiSuccess {String} show_discount 看秀折扣(%)
     * @apiSuccess {String} hidden 是否允許隱身(0:關/1:開)
     *
     * @apiSuccessExample {json} 成功回應
     * {
    "status": 1,
    "msg": "成功",
    "data": {
    "faq": "http:\/\/localhost\/99\/static\/faq\/guardian.html",
    "guard_id": 1,
    "guardian_name": "黄色守护",
    "last_activate_date": "2019-12-10",
    "last_renewal_date": "2020-02-14",
    "expire_date": "2020-03-14",
    "hidden": 0,
    "renewal_count": 2,
    "guardian_permission": {
    "activate_notify": 0,
    "welcome_notify": 0,
    "shot_border": 0,
    "rename": 0,
    "rename_limit": 0,
    "feiping": 0,
    "feiping_count": 0,
    "chat_bg": 0,
    "chat_limit": 0,
    "chat_freq_limit": 0,
    "chat_length_limit": 0,
    "forbid": 0,
    "forbid_count": 0,
    "kick": 0,
    "kick_count": 0,
    "show_discount": 0,
    "hidden": 0
    }
    }
    }
     */
    public function myInfo()
    {
        try {
            $data = $this->guardianService->getMyInfo();

            $this->setStatus(1, '成功');
            $this->setRootData('data', $data);
            return $this->jsonOutput();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            $this->setStatus(999, 'api執行失敗');
            return $this->jsonOutput();
        }
    }

    /**
     * @api {post} /guardian/buy 開通守護
     * @apiGroup Guardian
     * @apiName 開通守護
     * @apiVersion 1.0.0
     *
     * @apiParam {Int} rid (主播房間)，0或沒傳表示走房間外開通.
     * @apiParam {Int} uid (用戶id)，0或沒傳表示走房間外開通.
     * @apiParam {Int} guardId (開通守護等級(1黃、2紫、3黑)).
     * @apiParam {Int} payType (消費類型(1開通、2續費)).
     * @apiParam {Int} daysType (開通天數對應，1：30天，2：90天，3：365天，避免被串改，不直接傳天數)
     *
     * @apiError (Error Status) 999 API執行錯誤
     *
     * @apiError (Error Status) 101 欲開通之守護系統方案未開啟
     * @apiError (Error Status) 102 用戶鑽石不足,無法開通
     * @apiError (Error Status) 103 用戶现在的级别已大於要開通/續費的等级
     * @apiError (Error Status) 104 用戶已開通該級別守護，故僅能續費该等級守護
     * @apiError (Error Status) 105 用戶尚未開通該級別守護，故無法續費
     *
     * @apiSuccess {Int} status 開通執行狀態(1為開通成功,1以外為執行失敗)
     * @apiSuccess {String} msg 執行結果敘述
     *
     * @apiSuccess {Date} expireDate 守護到期日 (yyyy-mm-dd)
     * @apiSuccess {String} payTypeName 開通類型(续费成功,开通成功)
     * @apiSuccess {String} guardianName 守護類型(1 => '黄色守护', 2 => '紫色守护', 3 => '黑色守护')
     *
     * @apiSuccessExample {json} 成功回應
    {
    "status": 1,
    "msg": "开通守护执行成功",
    "data": {
    "expireDate": "2037-07-09",
    "guardianName": "紫色守护",
    "payTypeName": "续费成功"
    }
    }
     */

    public function buy(GuardianBuy $request)
    {
        $uid = Auth::id();
        $user = Auth::user();
        $userGuardId = $user->guard_id;
        $userPoints = $user->points;
        $userRich = $user->rich;
        $userGuardEnd = $user->guard_end;
        $rid = $request->rid ?? 0;
        $guardId = $request->guardId;
        $payType = $request->payType;
        $daysType = $request->daysType;
        $origin = Auth::user()->origin;
        $now = Carbon::now();
        $nowDate = $now->copy()->toDateString();
        $currentYM  = $now->copy()->format('Ym');
        $currentYMD = $now->copy()->format('Ymd');
        $nowDateTime = Carbon::now()->copy()->toDateTimeString();
        $siteId = SiteSer::siteId();
        $valid_dayArr = array(1 => 30, 2 => 90, 3 => 365);
        $guardIdToGid = array(1 => 700004, 2 => 700005, 3 => 700006);
        $payTypeName = array(1 => '开通成功', 2 => '续费成功');
        $payTypeArr = array(1 => 'activate', 2 => 'renewal');
        $guardIdToName = array(1 => '黄色守护', 2 => '紫色守护', 3 => '黑色守护');
        $defaultImg = array(
            1 => 'c62e026d074d331bb98be8a63b4f5303',
            2 => '7628eb77abb956b94d5fdf75427c1b1b',
            3 => '553a1e24744251a208a2fd1afd6e6ba0'
        );

        try {
            DB::beginTransaction();

            //取得守護設定價格
            $key = $payTypeArr[$payType] . '_' . $valid_dayArr[$daysType];
            $saleKey = $key . '_sale';
            $getSalePrice = Redis::hGet('hguardian_info:' . $guardId, $saleKey);
            $getPrice = Redis::hget('hguardian_info:' . $guardId, $key);

            //檢核方案是否有開啟
            if (!$getPrice) {
                $this->setStatus(101, '守护系统' . $valid_dayArr[$daysType] . '天方案未啟用');
                return $this->jsonOutput();
            }

            //計算該開通或續約所需的價格為何
            if (!$getSalePrice) {
                $finalPrice = $getPrice;
            } else {
                $finalPrice = $getSalePrice;
            }

            //5.檢核用戶鑽石是否足夠，不足返回資訊，看前端需不需要跳到充值頁
            if (!($userPoints >= $finalPrice)) {
                $this->setStatus(102, '您的钻石不足');
                return $this->jsonOutput();
            }

            //6.檢核續費還是新開通，是否有符合規定
            if ($userGuardEnd >= $now) {//如有開通且未過期
                if ($userGuardId > $guardId) {
                    $this->setStatus(103, '您现在的级别已大於您要开通/续费的等级');
                    return $this->jsonOutput();
                } else if ($userGuardId == $guardId && $payType != 2) {
                    $this->setStatus(104, '您已开通该级别守护，仅能续费该守护等级');
                    return $this->jsonOutput();
                } else if ($userGuardId < $guardId && $payType != 1) {
                    //(原守護等級 < 開通/續約等級 && 不是新開通)
                    $this->setStatus(105, '您尚未开通该级别守护，无法续费');
                    return $this->jsonOutput();
                }
            } else {
                if ($payType == 2) {
                    $this->setStatus(105, '您尚未开通该级别守护，无法续费');
                    return $this->jsonOutput();
                }
            }

            //1.取得用戶守護大頭貼，房間內就撈主播海報(video_user_host)，房間外用官方固定的守護圖
            $headimg = DB::table('video_user_host')->where('id', $rid)->value('cover');

            if (!$headimg) {
                $headimg = $defaultImg[$guardId];
            }
            //計算守護到期日
            if (!$userGuardEnd) {
                $guardEndTime = $now->copy()->addDays($valid_dayArr[$daysType]);
            } else {
                if ($userGuardEnd >= $nowDate) {
                    if ($payType == 1) {
                        $guardEndTime = Carbon::parse($userGuardEnd)->copy()->addDays($valid_dayArr[$daysType]);
                    } else {
                        $guardEndTime = Carbon::parse($userGuardEnd)->copy()->subDay()->addDays($valid_dayArr[$daysType]);
                    }
                } else {
                    $guardEndTime = $now->copy()->addDays($valid_dayArr[$daysType]);
                }
            }

            //3.新增守護記錄
            $guardianRecordArr = array(
                'uid'         => $uid,
                'rid'         => $rid,
                'pay_date'    => $now->copy()->toDateString(),
                'valid_day'   => $valid_dayArr[$daysType],
                'pay'         => $finalPrice,
                'expire_date' => $guardEndTime->copy()->addDay()->toDateString(),
                'guard_id'    => $guardId,
                'pay_type'    => $payType,
                'created_at'  => $nowDateTime,
                'updated_at'  => $nowDateTime
            );

            DB::table('video_guardian')->insert($guardianRecordArr);

            //4.異動用戶資料
            $u['points'] = ($userPoints - $finalPrice); // 扣除鑽石後的餘額
            $u['rich'] = ($userRich + $finalPrice); // 增加用戶財富經驗值
            $u['lv_rich'] = $this->guardianService->getRichLv($u['rich']); // 計算用戶財富新等級
            $u['guard_id'] = $guardId; // 開通守護等級
            $u['guard_end'] = $guardEndTime->copy()->toDateString(); // 守護到期日
            $u['headimg'] = $headimg;
            $u['update_at'] = $nowDateTime;

            DB::table('video_user')->where('uid', $uid)
                ->update($u);

            //4.異動主播資料
            if ($rid) {
                $anchorExp = Users::select('exp')->where('uid', $rid)
                    ->value('exp');
                $a['exp'] = ($anchorExp + $finalPrice); // 增加主播經驗值
                $a['lv_exp'] = $this->guardianService->getAnchorLevel($a['exp']); // 計算主播新等級
                $a['update_at'] = $nowDateTime;

                DB::table('video_user')->where('uid', $rid)
                    ->update($a);
            }

            //5.新增送禮紀錄
            $sendGiftData = array(
                'send_uid' => $uid,
                'rec_uid'  => $rid,
                'gid'      => $guardIdToGid[$guardId],
                'gnum'     => 1,
                'rid'      => $rid,
                'points'   => $finalPrice,
                'rate'     => '50',
                'origin'   => $origin,
                'site_id'  => $siteId,
                'created'  => $nowDateTime
            );
            DB::table('video_mall_list')->insert($sendGiftData);

            $expireMsgDate = $guardEndTime->copy()->subDay()->toDateString();

            $message = [
                'category'  => 2,
                'mail_type' => 3,
                'rec_uid'   => $uid,
                'content'   => '守护开通成功提醒：您已成功开通' . $guardIdToName[$guardId] . ',到期日： ' . $expireMsgDate
            ];

            $messageService = resolve(MessageService::class);

            $messageService->sendSystemToUsersMessage($message);

            DB::commit();

            $this->setStatus(1, '开通守护执行成功');
        } catch (\Exception $e) {
            DB::rollback();

            Log::error($e->getMessage());
            $this->setStatus(999, '开通守护執行失敗');
            return $this->jsonOutput();
        }

        //redis
        //異動用戶資料
        $CheckUser =  Redis::exists('huser_info:' . $uid);

        if (!$CheckUser) {
            $userInfo = Users::where('uid', $uid)->first()->toArray();
            Redis::hMSet('huser_info:' . $uid, $userInfo);
            Redis::expire('huser_info:' . $uid, 216000);
        }

        Redis::hMSet('huser_info:' . $uid, $u);

        //4. if(用戶守護不是第一次開通，且開通等級比上一次大)
        if ($guardId > $userGuardId && $payType == 1) {
            Redis::del('sguardian_chat_interval:' . $uid);
            Redis::del('sguardian_rename_' . $currentYM . ':' . $uid);
            Redis::del('sguardian_feiping_' . $currentYM . ':' . $uid);
            Redis::del('sguardian_forbid_' . $currentYMD . ':' . $uid);
            Redis::del('sguardian_kick_' . $currentYMD . ':' . $uid);
        }

        //5. 更新個人排行榜資訊
        Redis::zIncrBy('zrank_rich_day:' . $siteId, $finalPrice, $uid);
        Redis::zIncrBy('zrank_rich_week:' . $siteId, $finalPrice, $uid);
        Redis::zIncrBy('zrank_rich_month:' . $currentYM . ':' . $siteId, $finalPrice, $uid);
        Redis::zIncrBy('zrank_rich_history:' . $siteId, $finalPrice, $uid);

        if ($rid) {
            //異動主播資料
            Redis::hMSet('huser_info:' . $rid, $a);
            Redis::hSet('hvediosKtv:' . $rid, 'cur_exp', $a['exp']);

            $diffWithNextMon = $now->copy()->diffInSeconds($now->copy()->addDays(7)->startOfWeek());//距離下週一的秒數

            $diffWithTomorrow = $now->copy()->diffInSeconds($now->copy()->tomorrow());

            //判斷是否新增白名單或是累積單場消費紀錄
            if (Redis::exists('hroom_whitelist_key:' . $uid)) {
                $whiteListKey = Redis::SMEMBERS('hroom_whitelist_key:' . $uid);

                $oneToMorePrice = Redis::hget('hroom_whitelist:' . $uid . ':' . $whiteListKey[0], 'points');

                if ($finalPrice >= $oneToMorePrice) {
                    $whiteList = Redis::hget('hroom_whitelist:' . $uid . ':' . $whiteListKey[0], 'uids');
                    Redis::hSet('hroom_whitelist:' . $rid . ':' . $whiteListKey[0], 'uids', $whiteList . ',' . $uid);
                    Redis::HINCRBY('hroom_whitelist:' . $rid . ':' . $whiteListKey[0], 'nums', 1);
                }
            }

            Redis::hIncrBy('one2many_statistic:' . $rid, $uid, $finalPrice);

            //8.(直播間內開通才要做)，新增點亮置頂次數
            $checkTopThreshold = Redis::hExists('hsite_config:' . $siteId, 'top_threshold');

            $topThreshold = Redis::hget('hsite_config:' . $siteId, 'top_threshold');

            if (!$checkTopThreshold) {
                Redis::expire('huser_recommend_anchor:' . $rid, $diffWithTomorrow);
            }

            if ($finalPrice >= $topThreshold) {
                Redis::HINCRBY('hsite_config:' . $siteId, 'huser_recommend_anchor:' . $rid, 1);
            }

            //更新房間排行榜資訊
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
            Redis::zIncrBy('zrank_pop_month:' . $currentYM, $finalPrice, $rid);
            Redis::zIncrBy('zrank_pop_history', $finalPrice, $rid);
            Redis::zIncrBy('zrank_order_today:' . $rid, $finalPrice, $uid);
            Redis::zIncrBy('zrange_gift_history:' . $rid, $finalPrice, $uid);

            //新增守護在線列表對應的redis key
            $checkUserOnline = Redis::hExists('hguardian_online:' . $rid, $uid);

            if (!$checkUserOnline) {
                Redis::hSet('hguardian_online:' . $rid, $uid, $uid);
            }
            //通知java直播間內開通
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

        $this->setData('expireDate', $expireMsgDate);
        $this->setData('guardianName', $guardIdToName[$guardId]);
        $this->setData('payTypeName', $payTypeName[$payType]);

        return $this->jsonOutput();
    }

    /**
     * @api {get} /guardian/history 取得守護消費紀錄
     * @apiGroup Guardian
     * @apiName history
     * @apiVersion 1.0.0
     *
     * @apiSuccessExample {json} 成功回應
     *{
    "status": 1,
    "msg": "OK",
    "data": {
    "list": {
    "current_page": 1,
    "data": [
    {
    "id": 54,
    "uid": 9493540,
    "pay_date": "2020-03-13",
    "valid_day": 30,
    "price": 100,
    "sale": 0,
    "pay": 100,
    "expire_date": "2020-04-13",
    "guard_id": 1,
    "pay_type": 1,
    "created_at": "2020-03-13 17:16:30",
    "updated_at": "2020-03-13 09:16:31"
    },
    {
    "id": 55,
    "uid": 9493540,
    "pay_date": "2020-03-14",
    "valid_day": 30,
    "price": 100,
    "sale": 0,
    "pay": 100,
    "expire_date": "2020-04-14",
    "guard_id": 2,
    "pay_type": 2,
    "created_at": "2020-03-13 17:17:05",
    "updated_at": "2020-03-13 09:17:05"
    }
    ],
    "first_page_url": "http:\/\/localhost\/api\/m\/guardian\/history?page=1",
    "from": 1,
    "last_page": 1,
    "last_page_url": "http:\/\/localhost\/api\/m\/guardian\/history?page=1",
    "next_page_url": null,
    "path": "http:\/\/localhost\/api\/m\/guardian\/history",
    "per_page": 15,
    "prev_page_url": null,
    "to": 2,
    "total": 2
    },
    "type": "guardian"
    }
    }
     */
    public function history()
    {
        try {
            $this->setStatus(1, 'OK');
            $this->setData('list', Auth::user()->guardian->paginate());
            $this->setData('type', 'guardian');
            return $this->jsonOutput();
        } catch (\Exception $e) {
            report($e);
            $this->setStatus(999, 'api執行失敗');
            return $this->jsonOutput();
        }
    }

    /**
     * @api {get} /guardian/hack/:uid/:key/:val 用戶守護後門(開發用)
     * @apiGroup Guardian
     * @apiName hack
     * @apiVersion 1.0.0
     *
     * @apiParam {Int} uid 用戶id
     * @apiParam {Int} key 異動欄位(guard_id:守護id/guard_expire_date:守護到期日)
     * @apiParam {Int} val 值
     *
     */
    public function hack($uid, $key, $val)
    {
        try {
//            dd($uid, $key, $val);

            if (empty($uid) || empty($key)) {
                $this->setStatus(0, '輸入不得為空');
                return $this->jsonOutput();
            }

            $user = Users::find($uid);
//            dd($user);
            $user->{$key} = $val;

            if ($key == 'guard_id' && $val == 0) {
                $user->guardian()->delete();
            }

            $user->save();

            $this->setStatus(1, 'OK');
            return $this->jsonOutput();

        } catch (\Exception $e) {
            report($e);
        }
    }
}

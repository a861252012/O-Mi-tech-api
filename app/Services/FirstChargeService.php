<?php
/**
 * 首充禮 服務
 * @date 2020/06/19
 */

namespace App\Services;

use App\Constants\LvRich;
use App\Facades\SiteSer;
use App\Models\Recharge;
use App\Services\Message\MessageService;
use App\Services\User\UserService;
use App\Repositories\UserItemRepository;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use function Psy\debug;

class FirstChargeService
{
    protected $userItemRepository;
    protected $userService;
    protected $recharge;
    protected $userAttrService;

    public function __construct(
        UserItemRepository $userItemRepository,
        UserService $userService,
        Recharge $recharge,
        UserAttrService $userAttrService
    ) {
        $this->userItemRepository = $userItemRepository;
        $this->userService = $userService;
        $this->recharge = $recharge;
        $this->userAttrService = $userAttrService;
    }

    public function firstCharge($uid, $skipTimeCheck = false)
    {
        $this->userService->cacheUserInfo($uid);
        $user = $this->userService->getUserInfo($uid);
        info('用戶資訊: ' . json_encode($user));
        if (!empty($user['first_charge_time'])) {
            return -1;
        }

        // 更新首充資訊
        $firstCharge = $this->recharge
            ->select('created')
            ->where('uid', $uid)
            ->whereIn('pay_type', [1, 4, 7])
            ->where('pay_status', 2)
            ->orderby('created', 'ASC')
            ->first();

        //更新用戶資訊
        $rich = (int)$user['rich'] + 500;
        $data = [
            'first_charge_time' => $firstCharge->created,
            'rich'    => $rich,
            'lv_rich' => LvRich::calcul($rich),
        ];
        $this->userService->updateUserInfo($uid, $data);
        info("首充更新用戶資訊: " . json_encode($data));

        //驗證是否符合首充豪禮條件
        if (($this->countRemainingTime($user['uid']) === 0 && !$skipTimeCheck) ||
            !empty($this->userAttrService->get($user['uid'], 'is_first_gift'))
        ) {
            return -1;
        }

        $gift = [
            ['item_id' => '1', 'uid' => $uid, 'status' => 0],
            ['item_id' => '2', 'uid' => $uid, 'status' => 0]
        ];

        DB::beginTransaction();

        //贈送首充禮
        $insertGift = $this->userItemRepository->insertGift($gift);

        if (!$insertGift) {
            DB::rollBack();
            Log::error($uid . '贈送首充禮錯誤');
            return 0;
        }

        //贈送 50 鑽
        $data = [
            'points'  => (int)$user['points'] + 50,
        ];
        $updateUser = $this->userService->updateUserInfo($uid, $data);

        if (!$updateUser) {
            DB::rollBack();
            Log::error($uid . ' 首充禮贈送 50 鑽更新時發生錯誤');
            return 0;
        }

        //新增充值紀錄(首充禮)
        $rechargeRecord = $this->recharge->insert([
            'uid'        => $uid,
            'points'     => 50,
            'paymoney'   => 0,
            'created'    => date('Y-m-d H:i:s'),
            'order_id'   => 'EX' . hexdec(uniqid()),
            'pay_type'   => 5,
            'pay_status' => 2,
            'del'        => 0,
            'nickname'   => $user['nickname'],
            'site_id'    => (int)$user['site_id'],
        ]);

        if (!$rechargeRecord) {
            DB::rollBack();
            Log::error($uid . '新增充值紀錄(首充禮)錯誤');
            return 0;
        }

        DB::commit();

        //新增手機端首充禮訊息
        $UserFirstChargeMsg = [
            'category'  => 1,
            'mail_type' => 3,
            'rec_uid'   => $uid,
            'content'   => __('messages.FirstChargeService.reminder_msg'),
            'site_id'   => $user['site_id'],
            'locale'    => App::getLocale(),
        ];

        resolve(MessageService::class)->sendSystemtranslate($UserFirstChargeMsg);

        $this->userAttrService->set($uid, 'is_first_gift', 1);
        $this->userAttrService->set($uid, 'first_gift', 1);

        return 1;
    }

    //計算用戶首充禮剩餘秒數
    public function countRemainingTime($uid): int
    {
        $milliSecondTimeStamp = $this->userAttrService->get($uid, 'first_charge_gift_start_time');

        //確認first_charge_gift_start_time是否存在
        if (!$milliSecondTimeStamp) {
            $this->userAttrService->set(
                $uid,
                'first_charge_gift_start_time',
                round(microtime(true) * 1000)
            );

            $milliSecondTimeStamp = $this->userAttrService->get($uid, 'first_charge_gift_start_time');
        }

        $firstChargeGiftTime = (int)substr($milliSecondTimeStamp, 0, -3);

        $remainingTime = 259200 - (time() - $firstChargeGiftTime);

        return ($remainingTime < 0) ? 0 : $remainingTime;
    }

    //是否顯示首充好禮icon (return: 1:顯示/0：不顯示)
    public function isShowFirstGiftIcon($uid): int
    {
        $user = $this->userService->getUserInfo($uid);

        //取得首充顯示旗標
        $firstGift = $this->userAttrService->get($uid, 'first_gift');

        if (!empty($firstGift)) {
            if ($firstGift == 0 && $this->countRemainingTime($uid) > 0) {
                return 1;
            }

            return 0;
        }

        //first_gift如為空,則判斷剩餘秒數及用戶是否充值過
        if ($this->countRemainingTime($uid) > 0 && empty($user['first_charge_time'])) {
            $this->userAttrService->set($uid, 'first_gift', 0);
            return 1;
        }

        $this->userAttrService->set($uid, 'first_gift', 1);
        return 0;
    }


    //驗證首充資格 (return: true:首充用戶/false：非首充用戶)
    //$skipTimeCheck 如為 true 則 不驗證用戶剩餘時間
    public function checkFirstChargeQualifications($uid, $skipTimeCheck = false): bool
    {
        $this->userService->cacheUserInfo($uid);

        $isFirstGift = $this->userAttrService->get($uid, 'is_first_gift');
        $receiverRemainingTime = $this->countRemainingTime($uid);
        $userFirstChargeTime = $this->userService->getUserInfo($uid, 'first_charge_time');
        if ($skipTimeCheck) {
            return $receiverRemainingTime > 0 && empty($isFirstGift);
        }

        if ($receiverRemainingTime > 0 && empty($userFirstChargeTime) && empty($isFirstGift)) {
            return true;
        }

        return false;
    }
}

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
use App\Services\User\userService;
use App\Repositories\UserItemRepository;
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

    public function firstCharge($uid, $trendNo = '', $points = 0)
    {
        $user = $this->userService->getUserInfo($uid);
        info('用戶資訊: ' . json_encode($user));

        $gift = [
            ['item_id' => '1', 'uid' => $uid, 'status' => 0],
            ['item_id' => '2', 'uid' => $uid, 'status' => 0]
        ];


        //贈送首充禮
        $insertGift = $this->userItemRepository->insertGift($gift);

        if (!$insertGift) {
            Log::error('贈送首充禮錯誤');
            return false;
        }

        //更新用戶資訊
        $data = [
            'first_charge_time' => date('Y-m-d H:i:s'),
            'rich'    => (int)$user['rich'] + 500,
            'lv_rich' => LvRich::calcul($user['rich'] + 500),
            'points'  => (int)$user['points'] + 50 + $points
        ];

        info("首充更新用戶資訊: " . json_encode($data));

        $updateUser = $this->userService->updateUserInfo($uid, $data);

        if (!$updateUser) {
            Log::error('更新用戶資訊錯誤');
            return false;
        }

        //新增充值紀錄(首充禮)
        $rechargeRecord = $this->recharge->insert([
            'uid'        => $uid,
            'points'     => 50,
            'paymoney'   => 0,
            'created'    => date('Y-m-d H:i:s'),
            'order_id'   => $trendNo,
            'pay_type'   => 5,
            'pay_status' => 2,
            'del'        => 0,
            'nickname'   => $user['nickname'],
            'site_id'    => (int)$user['site_id'],
        ]);

        if (!$rechargeRecord) {
            Log::error('新增充值紀錄(首充禮)錯誤');
            return false;
        }

        //新增手機端首充訊息
        $UserFirstChargeMsg = [
            'mail_type' => 3,
            'rec_uid' => $uid,
            'content' => '恭喜你获得首充豪礼，贵族体验券、等级积分、反馈钻石、飞频均已发送，若有问题请洽客服人员。',
            'site_id' => $user['site_id']
        ];

        $sendMsg = resolve(MessageService::class)->sendSystemtranslate($UserFirstChargeMsg);

        if (!$sendMsg) {
            Log::error('新增手機端首充訊息錯誤');
            return false;
        }

        return true;
    }

    //計算用戶首充禮剩餘秒數
    public function countRemainingTime($uid): int
    {
        $milliSecondTimeStamp = $this->userAttrService->get($uid, 'first_charge_gift_start_time');

        //確認first_charge_gift_start_time是否存在
        if (!$milliSecondTimeStamp) {
            $milliSecondTimeStamp = $this->userAttrService->set(
                $uid,
                'first_charge_gift_start_time',
                round(microtime(true) * 1000)
            );
        }

        $firstChargeGiftTime = (int)substr($milliSecondTimeStamp, 0, -3);

        $remainingTime = 259200 - (time() - $firstChargeGiftTime);

        return ($remainingTime < 0) ? 0 : $remainingTime;
    }

    //是否顯示首充好禮icon (1:顯示/0：不顯示)
    public function isShowFirstGiftIcon($uid): int
    {
        $user = $this->userService->getUserInfo($uid);

        //非首充
        $isFirstGift = $this->userAttrService->get($uid, 'first_gift');

        if ($isFirstGift != null) {
            return $isFirstGift;
        }

        //first_gift如為空,則判斷剩餘秒數及用戶是否充值過
        if ($this->countRemainingTime($uid) > 0 && $user['first_charge_time'] === null) {
            $this->userAttrService->set($uid, 'first_gift', 1);

            return 1;
        }

        $this->userAttrService->set($uid, 'first_gift', 0);

        return 0;
    }
}

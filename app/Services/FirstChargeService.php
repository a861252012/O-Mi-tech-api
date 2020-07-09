<?php
/**
 * 首充禮 服務
 * @date 2020/06/19
 */

namespace App\Services;

use App\Constants\LvRich;
use App\Facades\SiteSer;
use App\Models\Recharge;
use App\Services\User\userService;
use App\Repositories\UserItemRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

    public function firstCharge($trendNo)
    {
        $gift = [
            ['item_id' => '1', 'uid' => Auth::id(), 'status' => 0],
            ['item_id' => '2', 'uid' => Auth::id(), 'status' => 0]
        ];

        DB::beginTransaction();

        //贈送首充禮
        $insertGift = $this->userItemRepository->insertGift($gift);

        if (!$insertGift) {
            Log::error('贈送首充禮錯誤');
            DB::rollBack();
            return false;
        }

        //更新用戶資訊
        $data = [
            'rich'    => Auth::user()->rich + 500,
            'lv_rich' => LvRich::calcul(Auth::user()->rich + 500),
            'points'  => Auth::user()->points + 50
        ];

        $updateUser = $this->userService->updateUserInfo(Auth::id(), $data);

        if (!$updateUser) {
            Log::error('更新用戶資訊錯誤');
            DB::rollBack();
            return false;
        }

        //新增充值紀錄(首充禮)
        $rechargeRecord = $this->recharge->insert([
            'uid'        => Auth::id(),
            'points'     => 50,
            'paymoney'   => 0,
            'created'    => date('Y-m-d H:i:s'),
            'order_id'   => $trendNo,
            'pay_type'   => 5,
            'pay_status' => 2,
            'del'        => 0,
            'nickname'   => Auth::user()->nickname,
            'site_id'    => SiteSer::siteId(),
        ]);

        if (!$rechargeRecord) {
            Log::error('新增充值紀錄(首充禮)');
            DB::rollBack();
            return false;
        }
        DB::commit();

        return true;
    }

    //計算用戶首充禮剩餘秒數
    public function countRemainingTime(): int
    {
        $milliSecondTimeStamp = $this->userAttrService->get('first_charge_gift_start_time');

        //確認first_charge_gift_start_time是否存在
        if (!$milliSecondTimeStamp) {
            $this->userAttrService->set('first_charge_gift_start_time', round(microtime(true) * 1000));
        }

        $firstChargeGiftTime = (int)substr($milliSecondTimeStamp, 0, -3);

        $remainingTime = 259200 - (time() - $firstChargeGiftTime);

        return ($remainingTime < 0) ? 0 : $remainingTime;
    }

    //驗證是否符合首充豪禮條件
    public function checkFirstGift(): int
    {
        //非首充
        if (Auth::user()->first_charge_time) {
            return 0;
        }

        //驗證剩餘秒數是否歸零,是否已領取首充禮
        if ($this->userAttrService->get('first_gift') == 1 || $this->countRemainingTime() == 0) {
            return 0;
        }

        return 1;
    }
}

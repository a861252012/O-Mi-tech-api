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


    public function __construct(
        UserItemRepository $userItemRepository,
        userService $userService,
        Recharge $recharge
    ) {
        $this->userItemRepository = $userItemRepository;
        $this->userService = $userService;
        $this->recharge = $recharge;
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
}

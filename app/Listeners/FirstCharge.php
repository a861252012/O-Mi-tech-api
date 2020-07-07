<?php

namespace App\Listeners;

use App\Constants\LvRich;
use App\Events\FirstGift;
use App\Facades\SiteSer;
use App\Models\Recharge;
use App\Repositories\UserItemRepository;
use App\Services\User\UserService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use DB;

class FirstCharge
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(
        UserItemRepository $userItemRepository,
        UserService $userService,
        Recharge $recharge
    ) {
        $this->userItemRepository = $userItemRepository;
        $this->userService = $userService;
        $this->recharge = $recharge;
    }

    /**
     * Handle the event.
     *
     * @return bool
     */
    public function handle(FirstGift $request)
    {
        $user = Auth::user();
        $trendNo = $request->trendNo;

        $gift = [
            ['item_id' => '1', 'uid' => Auth::id(), 'status' => 0],
            ['item_id' => '2', 'uid' => Auth::id(), 'status' => 0]
        ];

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
            'lv_rich' => LvRich::calcul($user->rich + 500),
            'points'  => Auth::user()->points + 50
        ];

        $updateUser = $this->userService->updateUserInfo(Auth::id(), $data);

        if (!$updateUser) {
            Log::error('更新用戶資訊錯誤');
            DB::rollBack();
            return false;
        }

        //新增鑽石操作紀錄
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
            Log::error('新增鑽石操作紀錄');
            DB::rollBack();
            return false;
        }

        return true;
    }
}
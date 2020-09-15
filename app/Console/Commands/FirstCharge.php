<?php
/**
 * 首充補資料
 * @date 2020-08-10
 */

namespace App\Console\Commands;

use App\Constants\LvRich;
use App\Entities\UserItem;
use App\Facades\UserSer;
use App\Models\Recharge;
use App\Services\User\UserService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FirstCharge extends Command
{
    protected $recharge;
    protected $userItem;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'first-charge {act?} {arg1?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '首充補資料';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        Recharge $recharge,
        UserItem $userItem
    ) {
        parent::__construct();

        $this->recharge = $recharge;
        $this->userItem = $userItem;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $act = $this->argument('act');
        if (method_exists($this, $act)) {
            $this->$act();
            exit;
        }
        echo 'Method not exists!';
    }

    // 檢查應有首充時間而未填
    private function checkFirstChargeTime()
    {
        $this->info('checkFirstChargeTime');

        // 取得8/6後充值訂單
        $orders = $this->recharge->where('pay_status', 2)
            ->whereIn('pay_type', [1, 7])
            ->where('created', '>=', '2020-08-06 00:00:00')
            ->where('created', '<=', date('Y-m-d H:i:s'))
            ->orderBy('created', 'asc')
            ->get();

        // userService
        $userService = resolve(UserService::class);

        // check arg to see if update data or check only
        $arg1 = $this->argument('arg1');

        $cnt = 0;
        foreach ($orders as $order) {
            // 檢查用戶首充時間
            $user = $userService->getUserByUid($order['uid']);
            if ($user['first_charge_time'] != '') {
                continue;
            }

            // 找出用戶首充時間
            $firstCharge = $this->recharge
                ->select('created')
                ->where('uid', $user['uid'])
                ->whereIn('pay_type', [1, 4, 7])
                ->where('pay_status', 2)
                ->orderby('created', 'ASC')
                ->first();

            // log
            $this->warn($user['uid'].' '.$user['nickname'] .' => '. $firstCharge->created);

            if ($arg1 == 'run') {
                $rich = (int)$user['rich'] + 500;
                UserSer::updateUserInfo($user['uid'], [
                    'first_charge_time' => $firstCharge->created,
                    'rich'    => $rich,
                    'lv_rich' => LvRich::calcul($rich),
                ]);
            }
            $cnt++;
        }

        if ($arg1 == 'run') {
            $this->info("Update: {$cnt}");
        }
        $this->info("Total: {$cnt}");
    }

    // 首充補禮資料
    private function supplement()
    {
        /* 補50鑽人數 */
        $addPointCount = 0;

        /* 補首充禮包人數 */
        $addFirstGiftCount = 0;

        /* 補經驗人數 */
        $addExpCount = 0;

        /* 取得8/6後充值訂單 */
        $orders = $this->recharge->where('pay_status', 2)
            ->whereIn('pay_type', [1, 7])
            ->where('created', '>=', '2020-08-06 00:00:00')
            ->where('created', '<=', date('Y-m-d H:i:s'))
            ->orderBy('created', 'asc')
            ->get();

        foreach ($orders as $order) {
            /* 判斷uid在目前訂單時間前是否有充值訂單 */
            $isDeposited = $this->recharge->where('uid', $order->uid)
                ->whereIn('pay_type', [1, 4, 7])
                ->where('pay_status', 2)
                ->where('created', '<', $order->created)
                ->get();

            if ($isDeposited->count() > 0) {
                /* 過去有成功充值訂單，故略過 */
                continue;
            }

            $firstChargeTime = $order->created;

            /* 更新用戶首充時間 */
            UserSer::updateUserInfo($order->uid, ['first_charge_time' => $firstChargeTime]);

            /* 用戶是否有pay_type = 5(充值贈送)的訂單 */
            $payType5Order = $this->recharge->where('uid', $order->uid)
                ->where('pay_type', 5)
                ->where('pay_status', 2)
                ->where('created', '>=', '2020-08-06 00:00:00')
                ->where('points', 50)
                ->orderBy('id')
                ->first();

            /* 取得user */
            $user = DB::table('video_user')->where('uid', $order->uid)->first();

            /* 補經驗旗標 */
            $expFlag = false;

            if (empty($payType5Order)) {
                /* 無充值贈送訂單，則新增一筆，並將新增時間設定為8/6之後的最早充值訂單的時間 */
                $insertId = $this->recharge->insertGetId([
                    'uid'        => $order->uid,
                    'points'     => 50,
                    'paymoney'   => 0,
                    'created'    => $firstChargeTime,
                    'ttime'      => $firstChargeTime,
                    'order_id'   => 'EX' . hexdec(uniqid()),
                    'pay_type'   => 5,
                    'pay_status' => 2,
                    'nickname'   => $order->nickname,
                    'message'    => '補首充command',
                    'del'        => 0,
                    'site_id'    => $order->site_id,
                ]);

                /* 補50鑽 */
                if (!empty($insertId)) {
                    UserSer::updateUserInfo($order->uid, ['points'  => (int)$user->points + 50]);
                    $addPointCount++;
                    $expFlag = true;
                    $this->info("用戶UID({$order->uid}) 補50鑽成功");
                }

            } else {
                /* 有充值贈送訂單，將新增時間設定為8/6之後的最早充值訂單的時間 */
                $payType5Order->created = $firstChargeTime;
                $payType5Order->save();
            }

            /* 取得用戶物品數量 */
            $itemsCount = $this->userItem->where('uid', $order->uid)->count();

            /* 如無禮包，則補 */
            if ($itemsCount == 0) {
                $this->userItem->insert([
                    ['item_id' => '1', 'uid' => $order->uid, 'status' => 0],
                    ['item_id' => '2', 'uid' => $order->uid, 'status' => 0],
                ]);

                $addFirstGiftCount++;
                $expFlag = true;
                $this->info("用戶UID({$order->uid}) 補禮包成功");
            }

            /* 補經驗 */
            if ($expFlag) {
                UserSer::updateUserInfo($order->uid, [
                    'rich'    => (int)$user->rich + 500,
                    'lv_rich' => LvRich::calcul($user->rich + 500),
                ]);

                $addExpCount++;
            }
        }

        $this->info("補50鑽人數: {$addPointCount}");
        $this->info("補禮包人數: {$addFirstGiftCount}");
        $this->info("補經驗人數: {$addExpCount}");
    }
}

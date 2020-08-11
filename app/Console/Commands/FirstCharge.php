<?php
/**
 * 首充補資料
 * @date 2020-08-10
 */
namespace App\Console\Commands;

use App\Constants\LvRich;
use App\Entities\UserItem;
use App\Models\Recharge;
use App\Repositories\UserItemRepository;
use App\Services\Message\MessageService;
use App\Services\User\UserService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FirstCharge extends Command
{
    protected $userService;
    protected $userItem;
    protected $userItemRepository;
    protected $recharge;
    protected $messageService;


    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'first-charge:supplement';

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
        UserItem $userItem,
        UserItemRepository $userItemRepository,
        UserService $userService,
        MessageService $messageService
    ) {
        parent::__construct();

        $this->recharge = $recharge;
        $this->userItem = $userItem;
        $this->userItemRepository = $userItemRepository;
        $this->userService = $userService;
        $this->messageService = $messageService;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        /* 取得時間區間充值用戶 */
        $users = $this->recharge->where('pay_status', 2)
                ->whereIn('pay_type',[1,4,7])
                ->whereBetween('created', ['2020-08-06', date('Y-m-d')])
                ->pluck('uid');

        if($users->isNotEmpty()) {
            DB::beginTransaction();

            foreach($users as $uid) {
                $result = $this->firstCharge($uid);
                if(empty($result)) {
                    DB::rollBack();
                }

                Log::debug("用戶ID({$uid})補首充 : {$result}");
                exit;
            }

            DB::commit();
        }
    }

    private function firstCharge($uid)
    {
        $user = $this->userService->getUserInfo($uid);
        info('用戶資訊: ' . json_encode($user));
        if(empty($user)) {
            return false;
        }

        //贈送首充禮
        $insertGift = $this->userItem->firstuOrCreate(['uid' => $uid, 'item_id' => '1'], ['status' => 0]);
        $insertGift2 = $this->userItem->firstuOrCreate(['uid' => $uid, 'item_id' => '2'], ['status' => 0]);

        if (!$insertGift || !$insertGift2) {
            Log::error('贈送首充禮錯誤');
            return false;
        }

        //更新用戶資訊
        if(empty($user->first_charge_time)) {
            $data = [
                'first_charge_time' => date('Y-m-d H:i:s'),
                'rich'    => (int)$user['rich'] + 500,
                'lv_rich' => LvRich::calcul($user['rich'] + 500),
                'points'  => (int)$user['points'] + 50
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
                'order_id'   => '_',
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
        }

        //新增手機端首充訊息
        $UserFirstChargeMsg = [
            'mail_type' => 3,
            'rec_uid' => $uid,
            'content' => '恭喜你获得首充豪礼，贵族体验券、等级积分、反馈钻石、飞频均已发送，若有问题请洽客服人员。',
            'site_id' => $user['site_id']
        ];

        $sendMsg = $this->messageService->sendSystemtranslate($UserFirstChargeMsg);

        if (!$sendMsg) {
            Log::error('新增手機端首充訊息錯誤');
            return false;
        }

        return true;
    }
}

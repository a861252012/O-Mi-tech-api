<?php
/**
 * 首充補資料
 * @date 2020-08-10
 */
namespace App\Console\Commands;

use App\Models\Recharge;
use App\Services\FirstChargeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FirstCharge extends Command
{
    protected $recharge;
    protected $firstChargeService;

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
        FirstChargeService $firstChargeService
    ) {
        parent::__construct();

        $this->recharge = $recharge;
        $this->firstChargeService = $firstChargeService;
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
                ->where('created', '>=', '2020-08-06 00:00:00')
                ->where('created', '<=', date('Y-m-d H:i:s'))
                ->pluck('uid');

        if($users->isNotEmpty()) {
            $this->info("需處理用戶數: " . $users->count());

            foreach($users as $uid) {
                $result = $this->firstChargeService->firstCharge($uid);

                if(empty($result)) {
                    $this->info("UID({$uid})補首充失敗，略過");
                    continue;
                }

                $this->info("用戶ID({$uid})補首充成功");
            }
        } else {
            $this->info("無用戶需處理");
        }
    }
}

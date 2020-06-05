<?php

namespace App\Console\Commands;

use App\Services\User\UserService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class Tools extends Command
{
    const SIX_MONTH_IN_SEC   = 15552000;
    const THREE_MONTH_IN_SEC =  7776000;
    const TWO_MONTH_IN_SEC   =  5184000;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tools {act?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'CLI Tools';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
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

    private function removeSidSixMonthOld()
    {
        $this->scanHUserSid(self::SIX_MONTH_IN_SEC);
    }
    private function removeSidThreeMonthOld()
    {
        $this->scanHUserSid(self::THREE_MONTH_IN_SEC);
    }
    private function removeSidTwoMonthOld()
    {
        $this->scanHUserSid(self::TWO_MONTH_IN_SEC);
    }

    private function scanHUserSid($diff = self::SIX_MONTH_IN_SEC)
    {
        $userService = resolve(UserService::class);
        $cursor = null;
        $cnt = 0;
        do {
            $sids = Redis::hscan('huser_sid', $cursor);
            $cursor = $sids[0];
            foreach ($sids[1] as $uid => $sid) {
                $userInfo = $userService->getUserByUid($uid);
                $logined = strtotime($userInfo->logined);
                if (time() - $logined > $diff) {
                    Redis::hdel('huser_sid', $uid);
                    echo 'DEL: ', $uid, ' ', date('Y-m-d H:i:s', $logined), "\n";

                    // delete
                    $userService->cacheUserInfo($uid, null);
                    $cnt++;
                }
            }
        } while ($cursor);

        echo 'Remove cnt: ', $cnt,"\n\n";
    }

}

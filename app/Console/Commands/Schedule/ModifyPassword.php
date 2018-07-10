<?php

namespace App\Console\Commands\Schedule;

use App\Models\Users;
use App\Services\Site\SiteService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class ModifyPassword extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'modify_password';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * @var SiteService
     */
    private $siteService;

    /**
     * Create a new command instance.
     *
     * @param SiteService $siteService
     */
    public function __construct(SiteService $siteService)
    {
        parent::__construct();
        $this->siteService = $siteService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->siteService->getIDs()
            ->each([$this, 'handleForSite']);
    }

    /**
     * 对pwd_change=1 的情况进行操作
     * @param $id
     * @return null
     */
    public function handleForSite($id)
    {
        $this->siteService->fromID($id);

        $mod_pwd_duration = $this->siteService->config('mod_pwd_duration');
        if (empty($mod_pwd_duration)) {
            return null;
        }

        $mod_date = date('Y-m-d H:i:s', time() - $mod_pwd_duration);

        $start = 0;
        $limit = 100;
        //修改时间+30 < 现在
        $update_data = ['pwd_change' => 0];
        $where_user = Users::query()->where('status', 0)->where('pwd_change', '1')->where('pwd_change', '<', $mod_date);
        while (!empty($userObj = $where_user->offset($start)->take($limit)->get())) {
            $uidArray = $userObj->pluck('uid')->toArray();
            $num = $where_user->whereIn('uid', $uidArray)->update($update_data);

            foreach ($uidArray as $uid) {
                Redis::exists("huser_info:" . $uid) && Redis::hmset("huser_info:" . $uid, $update_data);
            }
            //Log::channel('crontab')->info("");
            echo '更新数据：' . $num . '用户ID' . implode(',', $uidArray) . PHP_EOL;
            usleep(500);
        }
        return null;
    }
}

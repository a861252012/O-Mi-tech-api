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
     * @return void
     */
    public function __construct(SiteService $siteService)
    {
        parent::__construct();
        $this->siteService = $siteService;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
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
        if (empty($mod_pwd_duration)) return null;

        $mod_date = date('Y-m-d H:i:s', time() - $mod_pwd_duration);
        //修改时间+30 < 现在
        $user = Users::query()->where('status', 0)->where('pwd_change', '1')->where('pwd_change', '<', $mod_date)->take(100);
        $num = $user->update(['pwd_change' => 0]);
        echo $num;
    }
}

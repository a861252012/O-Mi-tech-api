<?php

namespace App\Console\Commands\Schedule;

use App\Services\Site\SiteService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

class AnchorList extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'anchor_list';

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

    public function handleForSite($id)
    {
        $this->siteService->fromID($id);
        if (!$this->siteService->isValid()){
            $this->info('invalid site config for id '.$id);
            Log::error('invalid site config  ',['id'=>$id]);
            return;
        }
        $flashVer = $this->siteService->config('flash_version');
        $this->info('flash_version:' . $flashVer);
//home_all_,home_rec_,home_ord_,home_gen_,home_vip_
        $conf_arr = [
            'home_all_' => ['所有主播', 'all'],
            'home_rec_' => ['小编推荐', 'rec'],
            'home_ord_' => ['一对一房间', 'ord'],
            'home_gen_' => ['才艺主播', 'gen'],
            //'home_vip_'=> array('会员专区','vip'),
            'home_mobile_' => ['手机直播', 'mobile'],
            'home_one_many_'=> ['一对多','ticket'],
        ];
//$json = '{';
        foreach ($conf_arr as $key => $item) {
            $data = Redis::get($key . $flashVer);//todo 区分站点
            if ($data == null) {
                echo $item[0] . '可能出问题了，请联系java开发人员' . PHP_EOL;
                Storage::disk('public')->put($this->siteService->getPublicPath() . '/videolist' . $item[1] . '.json', '{"rooms":[]}');
            } else {
                $data = str_replace(['cb(', ');'], ['', ''], $data);
                Storage::disk('public')->put($this->siteService->getPublicPath() . '/videolist' . $item[1] . '.json', $data);
            }
        }
    }
}

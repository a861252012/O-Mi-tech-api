<?php

namespace App\Console\Commands\Schedule;

use App\Services\Site\SiteService;
use Illuminate\Cache\FileStore;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\File;
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
        $flashVer =  $this->siteService->config('flash_version');
        $this->info('flash_version'.$flashVer);
//home_all_,home_rec_,home_ord_,home_gen_,home_vip_
        $conf_arr = array(
            'home_all_'=> array('所有主播','all'),
            'home_rec_'=> array( '小编推荐','rec'),
            'home_ord_'=> array('一对一房间','ord'),
            'home_gen_'=> array('才艺主播','gen'),
            //'home_vip_'=> array('会员专区','vip'),
            'home_mobile_'=> array('手机直播','mobile'),
        );
//$json = '{';
        foreach( $conf_arr as $key=>$item ){
            $data =  Redis::get($key.$flashVer);
            if( $data == null ){
                echo $item[0].'可能出问题了，请联系java开发人员'.PHP_EOL;
                Storage::disk('public')->put('videolist'.$item[1].'.json','{"rooms":[]}');
            }else{
                $data = str_replace(array('cb(',');'),array('',''),$data);
                Storage::disk('public')->put('videolist'.$item[1].'.json',$data);
            }
        }
    }
}

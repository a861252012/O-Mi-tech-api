<?php

namespace App\Console\Commands;

use App\Services\SiteService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

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
    public function handle(SiteService $siteService)
    {
        //
        define('APP_DIR', public_path());
       // $_redisInstance = resolve('redis')->connections();
        $flashVer = Redis::get('flash_version');
        echo $flashVer;
        die;
        !$flashVer && $flashVer = 'v201504092044';
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
            $data =  $_redisInstance->get($key.$flashVer);
            if( $data == null ){
                echo $item[0].'可能出问题了，请联系java开发人员'.PHP_EOL;
                file_put_contents(APP_DIR.'/videolist'.$item[1].'.json','{"rooms":[]}');
            }else{
                // $json .= $item[1].':'.$data;
                $data = str_replace(array('cb(',');'),array('',''),$data);
                file_put_contents(APP_DIR.'/videolist'.$item[1].'.json',$data);
            }
        }
    }
}

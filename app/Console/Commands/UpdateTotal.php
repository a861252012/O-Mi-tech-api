<?php

namespace App\Console\Commands;

use App\Models\Ad;
use App\Models\AdTotal;
use App\Services\Site\SiteService;
use Illuminate\Cache\FileStore;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

class UpdateTotal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update_total';

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
    public function handle()
    {
        $redis = Redis::resolve();
        /**
         * 更新广告统计
         */
        $ads = Ad::query()->where('total',1)->where('status',1)->get();
        foreach($ads as  $ad) {
            $key = 'total_ads:'.$ad['id'];
            if($redis->exists($key)){
                $date = date('Ymd', strtotime('-1 day'));
                $value = $redis->hGet($key, $date);
                $ad_total = AdTotal::query()->where('date',$date)->first();
                if($ad_total){
                    if($value > $ad_total['value']){
                        AdTotal::query()->where('id',$ad_total['id'])->update([
                            'value'=>$value
                        ]);
                    }
                }else{
                    AdTotal::create(array('aid'=>$ad['id'], 'date'=>$date, 'value'=>$value));
                }
            }
        }
    }
}

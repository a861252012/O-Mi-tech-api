<?php

namespace App\Console\Commands\Schedule;

use App\Models\UserBuyOneToMore;
use App\Models\VideoMail;
use App\Services\Site\SiteService;
use Illuminate\Cache\FileStore;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\File;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use App\Traits\SiteSpecific;
use App\Facades\SiteSer;

class One2ManyMsgSend extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    use SiteSpecific;
    protected $signature = 'one2many_msg';

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
        echo "1213";
        die;

        //todo use redis keys
        $start = Carbon::now()->addMinutes(2)->addSeconds(30);// date('Y-m-d H:i:s', time() + 150);
        $end = Carbon::now()->addMinutes(7)->addSeconds(30); //date('Y-m-d H:i:s', time() + 450);
        $keys = UserBuyOneToMore::query()
            ->whereBetween('starttime', [$start, $end])
            ->get();
//一个主播只发一次消息
        $zb_msg_sent = [];
        foreach ($keys as $room) {
            if (!Redis::exists("hbuy_one_to_more:" . $room['id'])) {
                continue;
            }

            if (!in_array($room['rid'], $zb_msg_sent)) {//一个主播只发一次消息
                VideoMail::create([
                    'send_uid' => 0,
                    'rec_uid' => $room['rid'],
                    'content' => '您开设的' . $room['starttime'] . '一对多约会房间快要开始了,请做好准备哦',
                    'category' => 1,
                    'status' => 0,
                    'created' => date('Y-m-d H:i:s'),
                    'site_id' => $room['site_id']

                ]);
                $zb_msg_sent[] = $room['rid'];
            }

            VideoMail::create([
                'send_uid' => 0,
                'rec_uid' => $room['uid'],
                'content' => '您预约的一对多房间，5分钟后开启，赶快进入直播间吧！',
                'category' => 1,
                'status' => 0,
                'created' => date('Y-m-d H:i:s')
            ]);
        }
    }
}

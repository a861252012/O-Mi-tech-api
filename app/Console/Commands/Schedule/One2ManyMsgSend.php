<?php

namespace App\Console\Commands\Schedule;

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
    protected $signature = 'command:one2many_msg';

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
        echo "1213";die;
        $_redisInstance = Redis::resolve();

        $keys = $_redisInstance->getKeys('hbuy_one_to_more:*');
//一个主播只发一次消息
        $zb_msg_sent = [];
        foreach ($keys as $item) {
            $room = $_redisInstance->hGetAll($item);
            $timecheck = date('Y-m-d H:i:s', strtotime($room['starttime']));
            $start = Carbon::now()->addMinutes(2)->addSeconds(30);// date('Y-m-d H:i:s', time() + 150);
            $end = Carbon::now()->addMinutes(7)->addSeconds(30); //date('Y-m-d H:i:s', time() + 450);
            if ($start < $timecheck && $end > $timecheck) {
                if (1) {
                    if (!in_array($room['rid'], $zb_msg_sent)) {//一个主播只发一次消息
                        pdoAdd(array('send_uid' => 0,
                            'rec_uid' => $room['rid'],
                            'content' => '您开设的' . $room['starttime'] . '一对多约会房间快要开始了,请做好准备哦',
                            'category' => 1,
                            'status' => 0,
                            'created' => date('Y-m-d H:i:s'),
                            'site_id' => SiteSer::siteId()),'video_mail'
                        );
                        VideoMail::create([
                                'send_uid' => 0,
                            'rec_uid' => $room['rid'],
                            'content' => '您开设的' . $room['starttime'] . '一对多约会房间快要开始了,请做好准备哦',
                            'category' => 1,
                            'status' => 0,
                            'created' => date('Y-m-d H:i:s')

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
    }
}

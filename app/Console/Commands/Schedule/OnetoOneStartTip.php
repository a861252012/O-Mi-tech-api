<?php

namespace App\Console\Commands\Schedule;

use App\Models\VideoMail;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Redis;

class OnetoOneStartTip extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'one_to_one_start_tip';

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
        $_redisInstance = Redis::resolve();
        $keys = $_redisInstance->getKeys('hroom_duration:*');
        foreach( $keys as $item ){
            $roomlist = $_redisInstance->hGetAll($item);
            foreach($roomlist as $room){
                $room = json_decode($room,true);
                $timecheck = Carbon::createFromFormat('Y-m-d H:i:s',$room['starttime']);//date('Y-m-d H:i:s',strtotime($room['starttime']));
                $start = Carbon::now()->addMinutes(2)->addSeconds(30);
                $end = Carbon::now()->addMinutes(7)->addSeconds(30);
                if($start<$timecheck&&$end>$timecheck){
                    if( $room['status']==0 &&  $room['reuid']!=0 ){
                        VideoMail::create(
                            array( 'send_uid'=> 0,
                                'rec_uid'=> $room['uid'],
                                'content'=>'您开设的'.$room['starttime'].'一对一约会房间快要开始了,请做好准备哦',
                                'category'=> 1,
                                'status' => 0,
                                'created'=> date('Y-m-d H:i:s')
                            )
                        );
                        VideoMail::create(
                            array( 'send_uid'=> 0,
                            'rec_uid'=> $room['reuid'],
                            'content'=>'您预约的一对一预约房间'.$room['starttime'].'快要开始了，请做好准备哦',
                            'category'=> 1,
                            'status' => 0,
                            'created'=> date('Y-m-d H:i:s')
                        ));
                    }
                }
            }
        }
    }
}

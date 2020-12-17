<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class RdpEnv extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rdp:env';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Preparing env for RDP';

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
        // videolistall
        $public_path = rtrim(public_path(), '/');
        $video_list = $public_path .'/storage/s1/videolistall.json';
        $data = json_decode(file_get_contents($video_list), true);
        foreach ($data['rooms'] as &$room) {
            $room['live_status'] = 1;
            $room['jump_egg_use_status'] = 1;
        }
        file_put_contents($video_list, json_encode($data, JSON_UNESCAPED_UNICODE));

        while (1) {
            // channel_update
            echo date("== Y-m-d H:i:s =="). " Update channel_update\n";
            $cu = Redis::hgetall('channel_update');
            foreach ($cu as $k => &$v) {
                $v = time() * 1000;
            }
            Redis::hmset('channel_update', $cu);

            sleep(10);
        }
    }
}

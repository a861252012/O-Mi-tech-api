<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class GuardianCover extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'guardian:cover';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '守護功能 - 主播海報資料建立';

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
        $cur = null;

        while ($c = Redis::hscan('hroom_ids', $cur)) {

        }


        dd($roomIds, $cur);
    }
}

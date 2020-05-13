<?php

namespace App\Console\Commands;

use App\Entities\Guardian;
use App\Models\GiftList;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class updateMonthRank extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:updateMonthRank';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'update anchor month rank';

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
        //取得月排行錯誤的rid
        $ridArray = Guardian::select('rid')->where('rid', '!=', 0)->whereBetween(
            'pay_date',
            [
                Carbon::now()->copy()->firstOfMonth()->toDateString(),
                Carbon::now()->copy()->endOfMonth()->toDateString()
            ]
        )->distinct()
            ->get()
            ->pluck('rid')
            ->toArray();

        //取得主播本月總收入
        foreach ($ridArray as $v) {
            $sumOfThisMonth = GiftList::where('rec_uid', $v)->where('gid', '!=', '410001')->whereBetween(
                'created',
                [
                    Carbon::now()->copy()->firstOfMonth(),
                    Carbon::now()->copy()->endOfMonth()->toDateTimeString()
                ]
            )->sum('points');

            //更新redis月排行
            Redis::zAdd('zrank_pop_month:' . Carbon::now()->copy()->format('Ym'), $sumOfThisMonth, $v);
        }

        //刪除原先錯誤的redis key
        for ($x = 1; $x <= 31; $x++) {
            if ($x < 10) {
                $x = '0' . $x;
            }
            Redis::del('zrank_pop_month:' . Carbon::now()->copy()->format('Ym') . $x);
        }
    }
}

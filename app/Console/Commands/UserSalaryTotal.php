<?php

namespace App\Console\Commands;

use App\Models\Ad;
use App\Models\AdTotal;
use App\Models\LiveList;
use App\Models\MallList;
use App\Models\Salary;
use App\Models\SalaryRule;
use App\Models\Users;
use App\Services\SiteService;
use Illuminate\Cache\FileStore;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

class UserSalaryTotal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'salary_total';

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
         * 核算主播工资
         * @author dc
         * @version 20160702
         */
        $date = strtotime('-1 day');
        $totalTime = date('Y-m-d 00:00:00', $date);
        $limitTime = date('Y-m-d 23:59:59',$date);
        $startTime = date('Y-m-d 00:00:00', strtotime("-1 day", $date));

        $total_time_int = strtotime($totalTime);
        $limit_time_int = strtotime($limitTime);

        $rules = SalaryRule::query()->get()->toArray();
        if(empty($rules)) die('empty salary rule!');

        /*
        $sql = <<<EOF
        select * from video_live_list where (start_time < '{$end}' and start_time > '{$start}') or (
                  start_time < '{$start}' and UNIX_TIMESTAMP(start_time)+duration < {$iEnd} and UNIX_TIMESTAMP(start_time)+duration > {$iStart})

        EOF;
        */

        $lives = LiveList::query()->whereBetween('start_time',[ $startTime,$limitTime])->orderBy('start_time')->get();
        $salary = $lives_total = array();
        $is_ruled = false;
        foreach($lives as $i=>$live) {

            //跨天运算
            $live_start_int = strtotime($live['start_time']);

            if($live_start_int + $live['duration'] < $total_time_int) {
                continue;
            }

            if($live_start_int < $total_time_int){

                $live['duration'] = $live['duration'] - ($total_time_int - $live_start_int);

            }elseif($live_start_int+$live['duration'] > $limit_time_int){

                $live['duration'] = $limit_time_int - $live_start_int;
            }

            if(isset($lives_total[$live['uid']])) {
                $lives_total[$live['uid']]['duration'] += $live['duration'];
                //$lives_total[$live['uid']]['points'] += $live['points'];
            }else{
                $lives_total[$live['uid']] = $live;
            }

        }//for merge

        foreach ($lives_total as $live) {
            $lv_type = $redis->hGet('huser_info:' . $live['uid'], 'lv_type');

            $user = Users::query()->where('uid',$live['uid'])->first();

            $lv_type = $lv_type ?: $user['lv_type'];

            $live['points'] = MallList::query()->where('rec_uid',$live['uid'])->where('gid','<>',4)->whereBetween('created',[$totalTime,$limitTime])->sum('points');
            foreach ($rules as $rule) {
                if ($rule['lv_type'] != $lv_type) continue;


                if ($live['duration'] >= $rule['time_min'] &&
                    $live['duration'] <= $rule['time_max'] &&
                    $live['points'] >= $rule['points_min'] &&
                    $live['points'] <= $rule['points_max']
                ) {

                    $salary[$live['uid']] = array(
                        'uid' => $live['uid'],
                        'lv_type' => $lv_type,
                        'date' => date('Y-m-d', $total_time_int),
                        'duration' => $live['duration'],
                        'points' => $live['points'],
                        'salary' => $rule['salary'],
                        'award' => $rule['award'],
                        'salary_total' => $rule['salary'] + $rule['award'],
                        'points_total' => (($rule['salary'] + $rule['award']) + (($rule['salary'] + $rule['award']) * ($rule['scale'] / 100))) * 10,

                    );

                }

            }

        }

        foreach($salary as $s){
            Salary::query()->where('uid',$s['uid'])->updateOrInsert(
                [
                    'uid'=>$s['uid'],'date'=>$s['date']
                ],$s);
        }
    }
}

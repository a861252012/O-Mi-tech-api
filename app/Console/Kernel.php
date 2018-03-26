<?php

namespace App\Console;

use App\Console\Commands\Schedule\UserClear;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Log\Logger;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $path = config('logging.channels.cron.path');
        ####定时生成更多列表的json文件####
        //*/1 * * * * /usr/local/bin/php /var/www/peach-front/crontab-list/anchor-all-list.php 1>/dev/null
        $schedule->command("anchor_list")->everyMinute()->withoutOverlapping();

        #########定时缓存主播搜索结果##########
        //*/1 * * * * /usr/local/bin/php /var/www/peach-front/crontab-list/anchor-search-list.php 1>/dev/null
        $schedule->command("anchor_search")->everyMinute()->withoutOverlapping()->onOneServer();

        $schedule->command("clear-user")->everyMinute()->withoutOverlapping();
        #########每五分钟定时推送预约房间开始信息##########
        //*/5 * * * * /usr/bin/php /var/www/video-front/crontab-list/duration-room-msg-send.php 1>/dev/null
        $schedule->command("one_to_one_start_tip")->everyFiveMinutes()->withoutOverlapping();

        //30 7 * * *  /usr/bin/php /var/www/video-front/crontab-list/everyday-clear-rediscache.php 1>/dev/null
        $schedule->command('vip_expire')->dailyAt('7:30')->withoutOverlapping();

        #########每天更新广告统计到数据库##########
        //0 1 * * *  /usr/bin/php /var/www/video-front/crontab-list/update-total.php 1>/dev/null
        $schedule->command('update_total')->dailyAt('1:00')->withoutOverlapping();

        #########用于主播工资核算##############
        //0 6 * * *  /usr/bin/php /var/www/video-front/crontab-list/user-salary-total.php>/dev/null
        $schedule->command('salary_total')->dailyAt('6:00')->withoutOverlapping();

        #########每五分钟定时推送一对多房间开始信息##########
        //*/5 * * * * /usr/bin/php /var/www/video-front/crontab-list/one2many-room-msg-send.php 1>/dev/null
        $schedule->command('one2many_msg')->everyFiveMinutes()->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}

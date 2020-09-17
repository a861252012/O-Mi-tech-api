<?php
/**
 * 加入獎勵跑道 事件
 * @author Weine
 * @date 2020-9-15
 */
namespace App\Listeners\Roulette;

use App\Events\RouletteReward;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Redis;

class News
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  RouletteReward  $event
     * @return void
     */
    public function handle(RouletteReward $event)
    {
        $now = time();
        foreach ($event->reward as $k => $item) {
            if ($item['broadcast']) {
                $newsData = [
                    'uid'      => $event->user->uid,
                    'nickname' => $event->user->nickname,
                    'type'     => $item['type'],
                    'amount'   => $item['amount'],
                ];

                Redis::zadd('zroulette_news', $now, json_encode($newsData));
            }
        }
    }
}

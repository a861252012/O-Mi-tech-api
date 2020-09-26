<?php
/**
 * 輪盤遊戲日排行 事件
 * @author Weine
 * @date 2020-9-15
 */

namespace App\Listeners\Roulette;

use App\Events\RouletteReward;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Redis;

class DailyRank
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
     * @param RouletteReward $event
     * @return void
     */
    public function handle(RouletteReward $event)
    {
        $reward = collect($event->reward)->mapToGroups(function ($item, $key) {
            return [$item['type'] => $item['amount']];
        })->toArray();

        $rankScore = collect($reward)->map(function ($item, $key) {
            return collect($item)->sum();
        })->sum();

        $rank = [
            'uid'      => $event->user->uid,
            'nickname' => $event->user->nickname,
            'items'    => $event->reward,
        ];

        Redis::zadd('zroulette_daily', $rankScore, json_encode($rank));
    }
}

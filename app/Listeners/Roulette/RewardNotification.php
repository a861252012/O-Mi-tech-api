<?php
/**
 * 中獎通知 事件
 * @author Weine
 * @date 2020-9-15
 */
namespace App\Listeners\Roulette;

use App\Events\RouletteReward;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Redis;

class RewardNotification
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
        $notification = [];
        foreach ($event->reward as $k => $item) {
            if ($item['broadcast']) {
                $notification[] = [
                    'uid'    => $event->user->uid,
                    'rid'    => $event->rid,
                    'type'   => $item['type'],
                    'amount' => $item['amount'],
                ];
            }
        }

        Redis::publish('p2j_roulette_broadcast', json_encode($notification));
    }
}

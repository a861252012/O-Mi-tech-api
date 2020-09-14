<?php

namespace App\Listeners\Roulette;

use App\Events\RouletteReward;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

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
        //
    }
}

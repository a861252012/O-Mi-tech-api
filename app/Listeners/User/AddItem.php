<?php

namespace App\Listeners\User;

use App\Events\RouletteReward;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class AddItem
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

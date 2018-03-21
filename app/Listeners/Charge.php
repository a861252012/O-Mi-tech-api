<?php

namespace App\Listeners;

use App\Events\Charge as ChargeEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class Charge
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
     * @param  Charge  $event
     * @return void
     */
    public function handle(ChargeEvent $event)
    {
        //
        $user = $event->user;
    }
}

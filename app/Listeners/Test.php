<?php

namespace App\Listeners;

use App\Events\Active;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class Test
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
     * @param  Active  $event
     * @return void
     */
    public function handle(Active $event)
    {
        //
        sleep(10);
        $user = $event->user;
        Log::info("test event:".$user->toJson());
        echo "1111";
    }
}

<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'App\Events\ShareUser' => [
            'App\Listeners\ShareReward',
            'App\Listeners\ShareRewardNotification',
        ],

        /* 輪盤遊戲獎勵事件 */
        'App\Events\RouletteReward' => [
            'App\Listeners\Roulette\AddUserReward',
            'App\Listeners\Roulette\DailyRank',
            'App\Listeners\Roulette\News',
            'App\Listeners\Roulette\RewardNotification',
        ],

        'App\Events\Event' => [
            'App\Listeners\EventListener',
        ],
        'App\Events\Active' => [
            'App\Listeners\Test',
        ],
        'App\Events\Charge' => [
            'App\Listeners\Charge',
        ],
//        'Illuminate\Auth\Events\Login' => [
//            'App\Listeners\SuccessfulLogin',
//        ],
        'App\Events\Login' => [
            'App\Listeners\SuccessfulLogin',
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        //
    }
}

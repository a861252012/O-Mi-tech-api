<?php

namespace App\Providers;

use App\Services\Room\RoomService;
use Illuminate\Support\ServiceProvider;

class RoomServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //roomService
        $this->app->singleton('roomService', function ($app) {
            return new RoomService($app['request']);
        });
        $this->app->singleton(RoomService::class);
    }
}

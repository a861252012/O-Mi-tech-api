<?php

namespace App\Providers;

use App\Services\Room\SocketService;
use Illuminate\Support\ServiceProvider;
use App\Services\Safe\SafeService;
use App\Services\System\SystemService;

class SocketServiceProvider extends ServiceProvider
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
        //
        $this->app->singleton('socketService', function () {
            return new SafeService();
        });
        $this->app->singleton('SystemService', function () {
            return new SystemService();
        });
    }
}

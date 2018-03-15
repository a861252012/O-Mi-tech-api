<?php

namespace App\Providers;

use App\Services\User\UserService;
use Illuminate\Container\Container;
use Illuminate\Support\ServiceProvider;

class UserServiceProvider extends ServiceProvider
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
        $this->app->singleton('userService', function ($app) {
            return new UserService($app['redis']);
        });
        $this->app->singleton('userServer', function ($app) {
            return new UserService($app['redis']);
        });
    }
}

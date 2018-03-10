<?php

namespace App\Providers;

use App\Services\JWTAuthService;
use Illuminate\Support\ServiceProvider;

class JWTAuthServiceProvider extends ServiceProvider
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
        $this->app->singleton('jwtAuth', function ($app) {
            return new JWTAuthService();
        });
    }
}

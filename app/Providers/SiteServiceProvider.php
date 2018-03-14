<?php

namespace App\Providers;

use App\Services\SiteService;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class SiteServiceProvider extends ServiceProvider
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
        $this->app->singleton('siteService', function () {
            return new SiteService;
        });
    }
}
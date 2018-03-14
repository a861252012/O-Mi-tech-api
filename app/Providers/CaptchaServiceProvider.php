<?php

namespace App\Providers;

use App\Services\SiteService;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class CaptchaServiceProvider extends ServiceProvider
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
    public   function register()
    {
        //
    //    parent::boot();
        //使用singleton绑定单例
        $this->app->singleton('test',function(){
            return new TestService();
        });

        var_dump('ddd');exit;
    }
    public function Verify(){

    }
}

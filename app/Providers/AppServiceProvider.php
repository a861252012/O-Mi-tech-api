<?php

namespace App\Providers;

use App\Services\Message\MessageService;
use App\Services\User\CaptchaService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app->singleton('captcha',function (){
            return new CaptchaService();
        });
        $this->app->singleton('messageService',function (){
            return new MessageService();
        });
        if (config('app.env') !== 'production' || config('app.debug') == true) {
            Artisan::call('route:clear');
            Artisan::call('config:clear');
        }
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
        if ($this->app->environment() !== 'production') {
            $this->app->register(\Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class);
        }
    }
}

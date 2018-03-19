<?php

namespace App\Providers;

use App\Events\Active;
use App\Services\ActiveService;
use App\Services\Charge\ChargeGroupService;
use App\Services\Charge\ChargeService;
use App\Services\Message\MessageService;
use App\Services\User\CaptchaService;
use App\Services\UserGroup\UserGroupService;
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
        $this->app->singleton('userGroupServer',function (){
            return new UserGroupService();
        });
        $this->app->singleton('active',function (){
            return new ActiveService();
        });
        $this->app->singleton('charge',function (){
            return new ChargeService();
        });
        $this->app->singleton('chargeGroup',function (){
            return new ChargeGroupService();
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

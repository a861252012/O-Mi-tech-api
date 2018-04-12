<?php

namespace App\Providers;

use App\Http\Middleware\MobileSession;
use App\Services\ActiveService;
use App\Services\Charge\ChargeGroupService;
use App\Services\Charge\ChargeService;
use App\Services\Message\MessageService;
use App\Services\Mobile\MobileService;
use App\Services\Room\One2MoreRoomService;
use App\Services\Room\One2OneRoomService;
use App\Services\Room\SocketService;
use App\Services\Safe\SafeService;
use App\Services\Site\SiteService;
use App\Services\System\SystemService;
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
        $this->app->singleton(UserGroupService::class);
        $this->app->singleton(ActiveService::class);
        $this->app->singleton(ChargeService::class);
        $this->app->singleton(ChargeGroupService::class);
        $this->app->singleton(MessageService::class);
        $this->app->singleton(MobileService::class);
        $this->app->singleton(SocketService::class);
        $this->app->singleton(SafeService::class);
        $this->app->singleton(SocketService::class);
        $this->app->singleton(SystemService::class);

        $this->app->singleton(MobileSession::class);


        $this->app->singleton('active', function () {
            return new ActiveService();
        });
        $this->app->singleton('charge', function () {
            return new ChargeService();
        });
        $this->app->singleton('chargeGroup', function () {
            return new ChargeGroupService();
        });
        $this->app->singleton('mobile', function () {
            return new MobileService();
        });
        $this->app->singleton('one2one', function ($app) {
            return new One2OneRoomService($app['request']);
        });
        $this->app->singleton('userGroupServer', function () {
            return new UserGroupService();
        });
        $this->app->singleton('one2more', function ($app) {
            return new One2MoreRoomService($app['request']);
        });

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

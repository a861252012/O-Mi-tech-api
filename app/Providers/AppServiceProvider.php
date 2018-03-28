<?php

namespace App\Providers;

use App\Http\Middleware\MobileSession;
use App\Services\ActiveService;
use App\Services\Charge\ChargeGroupService;
use App\Services\Charge\ChargeService;
use App\Services\Message\MessageService;
use App\Services\Mobile\MobileService;
use App\Services\Room\SocketService;
use App\Services\Safe\SafeService;
use App\Services\Site\SiteService;
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
        $this->app->singleton(SiteService::class);
        $this->app->singleton(MobileSession::class);
        $this->app->singleton(UserGroupService::class);
        $this->app->singleton(ActiveService::class);
        $this->app->singleton(ChargeService::class);
        $this->app->singleton(ChargeGroupService::class);
        $this->app->singleton(MessageService::class);
        $this->app->singleton(MobileService::class);

        $this->app->singleton('userGroupServer', function () {
            return new UserGroupService();
        });
        $this->app->singleton('active', function () {
            return new ActiveService();
        });
        $this->app->singleton('charge', function () {
            return new ChargeService();
        });
        $this->app->singleton('chargeGroup', function () {
            return new ChargeGroupService();
        });
        $this->app->singleton('messageService', function () {
            return new MessageService();
        });
        $this->app->singleton('safeService', function () {
            return new SafeService();
        });
        $this->app->singleton('mobile', function () {
            return new MobileService();
        });
        $this->app->singleton('socketService', function () {
            return new SocketService();
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

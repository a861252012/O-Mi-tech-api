<?php

namespace App\Providers;

use App\Services\Auth\JWTGuard;
use App\Services\Auth\RedisUserProvider;
use App\Services\Auth\WebAuthService;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        'App\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Auth::extend('jwt', function ($app, $name, array $config) {
            return new JWTGuard(Auth::createUserProvider($config['provider']), $app['request']);
        });
        Auth::provider('redisUsers', function ($app, array $config) {
            return new RedisUserProvider($app['redis'],$config['model']);
        });

        Auth::extend('pc', function ($app, $name, array $config) {
//            return new WebAuthService(Auth::createUserProvider($config['provider']));
        });
    }
}

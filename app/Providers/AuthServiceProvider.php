<?php

namespace App\Providers;

use App\Services\Auth\JWTGuard;
use App\Services\Auth\RedisUserProvider;
use App\Services\Auth\SessionGuard;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Auth;

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
            return new RedisUserProvider($app['redis'], $config['model']);
        });

        Auth::extend('sessionGuard', function ($app, $name, array $config) {
            $guard = new SessionGuard($name,
                Auth::createUserProvider($config['provider']),
                $app['session.store'],
                $app['request']);
            $guard->setCookieJar($this->app['cookie']);
            $guard->setDispatcher($this->app['events']);
            $guard->setRequest($this->app->refresh('request', $guard, 'setRequest'));
            return $guard;
        });
    }
}

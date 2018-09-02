<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class Test
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {

        if (config('app.debug') && $pid = $request->header('X-Test-V5')) {
            $auth = Auth::guard();
            $encrypter = app()->make('Illuminate\Encryption\Encrypter');
            $redis = app()->make('redis');
            $token = $redis->hget('huser_sid', $pid);
            $session = @unserialize(@unserialize($redis->get('PHPREDIS_SESSION:laravel:' . $token)));

            if ($session['webonline'] == $pid) {
                $sid = $encrypter->encrypt($token);
            } else if (is_numeric($pid)) {
                $auth->loginUsingId($pid);
                $sid = $encrypter->encrypt(Auth::getSession()->getId());
            } else {
                $fobj = new \ReflectionObject($auth);
                $provider = $fobj->getProperty('provider');
                $provider->setAccessible('true');
                $user = $provider->getValue($auth)->retrieveByCredentials(['username' => $pid, 'password' => '']);
                $lastAttempted = $fobj->getProperty('lastAttempted');
                $lastAttempted->setAccessible(1);
                $lastAttempted->setValue($auth, $user);
                $auth->login($user, 1);
                $sid = $encrypter->encrypt(Auth::getSession()->getId());
            }
            $request->cookies->set('SESSID', $sid);
        }
        return $next($request);
    }
}

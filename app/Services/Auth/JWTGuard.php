<?php

namespace App\Services\Auth;

use Illuminate\Auth\Events\Attempting;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\GuardHelpers;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\Request;
use Lcobucci\JWT\Builder as JWTBuilder;
use Lcobucci\JWT\Parser as JWTParser;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\ValidationData;

/**
 * Created by PhpStorm.
 * User: nicholas
 * Date: 2016/9/22
 * Time: 9:44
 */
class JWTGuard implements StatefulGuard
{
    use GuardHelpers;
    /** @var  $token Token */
    private $token;
    const guard='mobile';
    protected $_config;
    protected $request;
    protected $events;
    protected $lastAttempted;


    /**
     * JWTGuard constructor.
     * @param UserProvider $provider
     * @param Request $request
     */
    public function __construct(UserProvider $provider, Request $request)
    {
        $this->provider = $provider;
        $this->request = $request;
        $this->inputKey = 'jwt';
        $this->storageKey = null;
        $this->_config = [
            'alg' => 'HS256',
            'key' => 'testkey',
            'alg_classes' => [
                'HS256' => \Lcobucci\JWT\Signer\Hmac\Sha256::class,
                'HS384' => \Lcobucci\JWT\Signer\Hmac\Sha384::class,
                'HS512' => \Lcobucci\JWT\Signer\Hmac\Sha512::class,
                'RS256' => \Lcobucci\JWT\Signer\Rsa\Sha256::class,
                'RS384' => \Lcobucci\JWT\Signer\Rsa\Sha384::class,
                'RS512' => \Lcobucci\JWT\Signer\Rsa\Sha512::class,
                'ES256' => \Lcobucci\JWT\Signer\Ecdsa\Sha256::class,
                'ES384' => \Lcobucci\JWT\Signer\Ecdsa\Sha384::class,
                'ES512' => \Lcobucci\JWT\Signer\Ecdsa\Sha512::class,
            ],
            'expire' => 3650 * 24 * 60 * 60,//过期时间（秒）
        ];
    }

    /**
     * @param Authenticatable $user
     * @param bool $remember
     * @return bool|Token|void
     */
    public function login(Authenticatable $user, $remember = false)
    {

        $this->createToken([
            'uid' => $user->getAuthIdentifier(),
            'username' => $user->username,
        ]);
        $this->fireLoginEvent($user, $remember);
        $this->setUser($user);
    }

    protected function fireLoginEvent($user, $remember = false)
    {
        if (isset($this->events)) {
            $this->events->dispatch(new Login($user, $remember));
        }
    }

    protected function hasValidCredentials($user, $credentials)
    {
        return !is_null($user) && $this->provider->validateCredentials($user, $credentials);
    }

    protected function fireAttemptEvent(array $credentials, $remember = false)
    {
        if (isset($this->events)) {
            $this->events->dispatch(new Attempting(
                $credentials, $remember
            ));
        }
    }

    public function attempting($callback)
    {
        if (isset($this->events)) {
            $this->events->listen(Attempting::class, $callback);
        }
    }

    protected function fireFailedEvent($user, array $credentials)
    {
        if (isset($this->events)) {
            $this->events->dispatch(new Failed($user, $credentials));
        }
    }

    /**
     * Set the event dispatcher instance.
     *
     * @param  \Illuminate\Contracts\Events\Dispatcher $events
     * @return void
     */
    public function setDispatcher(Dispatcher $events)
    {
        $this->events = $events;
    }

    /**
     * Get the event dispatcher instance.
     *
     * @return \Illuminate\Contracts\Events\Dispatcher
     */
    public function getDispatcher()
    {
        return $this->events;
    }

    public function getLastAttempted()
    {
        return $this->lastAttempted;
    }

    public function attempt(array $credentials = array(), $remember = false, $login = true)
    {
        $this->fireAttemptEvent($credentials, $remember);
        $this->lastAttempted = $user = $this->provider->retrieveByCredentials($credentials);
        if ($this->hasValidCredentials($user, $credentials)) {
            $this->login($user, $remember);
            return true;
        }
        $this->fireFailedEvent($user, $credentials);
        return false;
    }

    /**
     * @return Token
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param array $claims
     * @param array $headers
     * @return JWTGuard
     */
    public function createToken(array $claims, array $headers = [])
    {
        $now = time();
        $token = (new JWTBuilder)
//            ->setIssuer('http://example.com')// Configures the issuer (iss claim)
//            ->setAudience('http://example.org')// Configures the audience (aud claim)
//            ->setId('4f1g23a12aa', true)// Configures the id (jti claim), replicating as a header item
            ->setIssuedAt($now)// Configures the time that the token was issue (iat claim)
//            ->setNotBefore(time() + 60)// Configures the time that the token can be used (nbf claim)
            ->setExpiration($now + $this->config('expire'))// Configures the expiration time of the token (nbf claim)
        ;
        foreach ($claims as $name => $value) {
            $token->set($name, $value);
        }
        $this->token = $token->sign($this->getSigner(), $this->config('key'))->getToken();
        return $this;
    }

    public function config($name, $value = null)
    {
        if (isset($value)) {
            $this->_config[$name] = $value;
            return $this;
        }
        if ($value === null) return isset($this->_config[$name]) ? $this->_config[$name] : null;
        return false;
    }

    protected function getSigner()
    {
        return (new \ReflectionClass($this->config('alg_classes')[$this->config('alg')]))->newInstance();
    }


    /**
     * @param $token
     * @return Authenticatable|null
     */
    public function getUserFromToken($token)
    {
        $this->parseToken($token);
        if (!$this->validateToken()) return null;
        $uid = $this->token->getClaim('uid');
        return $this->provider->retrieveById($uid);
    }

    /**
     * @param string $token
     * @return JWTGuard
     */
    public function parseToken($token)
    {
        $this->token = (new JWTParser())->parse($token);
        return $this;
    }

    /**
     * @return bool
     * @internal param Token $token
     */
    public function validateToken()
    {
        $validationData = new ValidationData;
        $validationData->setCurrentTime(time());
        /** @var */
        return $this->token->validate($validationData) && $this->token->verify($this->getSigner(), $this->config('key'));
    }

    /**
     * Get the token for the current request.
     *
     * @return string
     */
    public function getTokenForRequest()
    {
        $token = $this->request->query($this->inputKey);

        if (empty($token)) {
            $token = $this->request->input($this->inputKey);
        }

        if (empty($token)) {
            $token = $this->request->bearerToken();
        }

        if (empty($token)) {
            $token = $this->request->getPassword();
        }
        if (empty($token)) {
            $token = $this->request->headers->get('jwt');
        }

        return $token;
    }


    /**
     * Get the currently authenticated user.
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function user()
    {
        if (!is_null($this->user)) {
            return $this->user;
        }
        $user = null;
        $token = $this->getTokenForRequest();
        if (!empty($token)) {
            $user = $this->getUserFromToken($token);
        }

        return $this->user = $user;
    }
    public function getUser(){
        return $this->user();
    }

    /**
     * Validate a user's credentials.
     *
     * @param  array $credentials
     * @return bool
     */
    public function validate(array $credentials = [])
    {
        $this->lastAttempted = $user = $this->provider->retrieveByCredentials($credentials);

        return $this->hasValidCredentials($user, $credentials);
    }

    /**
     * Log a user into the application without sessions or cookies.
     *
     * @param  array $credentials
     * @return bool
     */
    public function once(array $credentials = [])
    {
        $this->fireAttemptEvent($credentials);

        if ($this->validate($credentials)) {
            $this->setUser($this->lastAttempted);

            return true;
        }

        return false;
    }

    /**
     * Log the given user ID into the application.
     *
     * @param  mixed $id
     * @param  bool $remember
     * @return \Illuminate\Contracts\Auth\Authenticatable
     */
    public function loginUsingId($id, $remember = false)
    {
        if (!is_null($user = $this->provider->retrieveById($id))) {
            $this->login($user, $remember);

            return $user;
        }

        return false;
    }

    /**
     * Log the given user ID into the application without sessions or cookies.
     *
     * @param  mixed $id
     * @return bool
     */
    public function onceUsingId($id)
    {
        // TODO: Implement onceUsingId() method.
    }

    /**
     * Determine if the user was authenticated via "remember me" cookie.
     *
     * @return bool
     */
    public function viaRemember()
    {
        // TODO: Implement viaRemember() method.
    }

    /**
     * Log the user out of the application.
     *
     * @return void
     */
    public function logout()
    {
        // TODO: Implement logout() method.
    }
}
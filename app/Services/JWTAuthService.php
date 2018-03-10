<?php
namespace App\Services;

use Core\Request;
use Core\Service;
use Illuminate\Container\Container;
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
class JWTAuthService
{
    /** @var  $token Token */
    public $token;
    protected $_config;
    protected $request;

    public function __construct(Container $container)
    {
        parent::__construct($container);
        $this->_config = $this->container['config']['jwt'];
    }

    public function login(array $credentials)
    {
        $userInfo = $this->attempt($credentials);
        if (!$userInfo) return false;
        $this->createToken([
            'uid' => $userInfo['uid'],
            'username' => $userInfo['username'],
        ]);
        return $this->token;
    }

    public function attempt(array $credentials)
    {


        $username = $credentials['username'];
        $password = $credentials['password'];
        $redis = $this->make('redis');
        $uid = $redis->hget('husername_to_id', $username);
        $_isGetCache = false;

        if (!$uid) {
//            $userInfo = Users::where('username', $username)->first();

            $userInfo = $this->make('userServer')->getUserByUsername($username);
        } else {
            $userInfo = $redis->hgetall('huser_info:' . $uid);

            if (!$userInfo) {
//               $userInfo = Users::find($uid)->toArray();
                $userInfo = $this->make('userServer')->getUserByUid($uid);
            } else {
                $_isGetCache = true;
            }
        }

        if (empty($userInfo) || $userInfo['password'] != md5($password)) {
            throw new LoginException('帐号密码错误!');
        }

        //后台设置了是否登录
        if ($userInfo['status'] != 1) {
            throw new LoginException('您的账号已经被禁止登录，请联系客服！');
        }
//        $huser_sid = $redis->hget('huser_sid', $uid);
        if (!$_isGetCache) {
            $redis->hset('husername_to_id', $userInfo['username'], $userInfo['uid']);
            $redis->hset('hnickname_to_id', $userInfo['nickname'], $userInfo['uid']);
//            $redis->hmset('huser_info:' . $userInfo['uid'], (array)$userInfo);
        }
        return $userInfo;
    }

    /**
     * @param array $claims
     * @param array $headers
     * @return JWTAuthService
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

    public function user()
    {
        return $this->getUserFromToken($this->token);
    }

    public function getUserFromToken($token)
    {
        $this->parseToken($token);
        if (!$this->validateToken()) return false;
        return [
            'uid' => $this->token->getClaim('uid'),
            'username' => $this->token->getClaim('username'),
        ];
    }

    /**
     * @param string $token
     * @return JWTAuthService
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

    public function getTokenFromRequest(Request $request)
    {
        if (($token = $this->getTokenFromHeader($request)) || ($token = $this->getTokenFromParameters($request))) {
            $this->token = $token;
            return true;
        }
        return false;
    }

    protected function getTokenFromHeader(Request $request)
    {
        return str_replace('Bearer ', '', $request->headers->get('Authorization')) ?: false;
    }

    protected function getTokenFromParameters(Request $request)
    {
        return $request->input('jwt', false);
    }
}
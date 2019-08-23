<?php
/**
 * Created by PhpStorm.
 * User: nicholas
 * Date: 2018/3/15
 * Time: 10:22
 */

namespace App\Services\Auth;


use App\Facades\UserSer;
use App\Services\User\UserService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Redis\RedisManager;

class RedisUserProvider implements UserProvider
{
    protected $redis;
    protected $model;

    public function __construct(RedisManager $redis, $model)
    {
        $this->redis = $redis;
        $this->model = $model;
    }

    /**
     * Retrieve a user by their unique identifier.
     *
     * @param  mixed $identifier
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveById($identifier)
    {
        $user = resolve(UserService::class)->getUserByUid($identifier);
        if ($user && $user->banned()) {
            throw new HttpResponseException(JsonResponse::create(['status' => 0, 'msg' => '您的账号已经被禁止登录，请联系客服！']));
        }
        return $user;
    }

    /**
     * Retrieve a user by their unique identifier and "remember me" token.
     *
     * @param  mixed $identifier
     * @param  string $token
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByToken($identifier, $token)
    {
        return null;
    }

    /**
     * Update the "remember me" token for the given user in storage.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable $user
     * @param  string $token
     * @return void
     */
    public function updateRememberToken(Authenticatable $user, $token)
    {

    }

    /**
     * Retrieve a user by the given credentials.
     *
     * @param  array $credentials
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByCredentials(array $credentials)
    {
        if (empty($credentials) ||
            (count($credentials) === 1 &&
                array_key_exists('password', $credentials))) {
            return;
        }

        if (isset($credentials['cc_mobile'])) {
            $user = UserSer::getUserByCCMobile($credentials['cc_mobile']);
        } else {
            $username = $credentials['username'];
            if (filter_var($username, FILTER_VALIDATE_EMAIL)) {
                $user = UserSer::getUserByUsername($username);
            } else {
                $user = UserSer::getUserByNickname($username);
                if (is_null($user)) {
                    $user = UserSer::getUserByUsername($username);
                }
            }
        }

        if ($user && $user->banned()) {
            throw new HttpResponseException(JsonResponse::create(['status' => 0, 'msg' => '您的账号已经被禁止登录，请联系客服！']));
        }
        return $user;
    }

    /**
     * Validate a user against the given credentials.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable $user
     * @param  array $credentials
     * @return bool
     */
    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        if (isset($credentials['cc_mobile']) && isset($credentials['mobile_logined']) === true) {
            return true;
        }

        $plain = $credentials['password'];
        return $user->getAuthPassword() === hash('md5', $plain);
    }

    /**
     * Create a new instance of the model.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function createModel()
    {
        $class = '\\' . ltrim($this->model, '\\');

        return new $class;
    }
}
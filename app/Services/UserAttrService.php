<?php
/**
 * 用戶資料關聯 服務
 * @author Weine
 * @date 2020-07-02
 */

namespace App\Services;

use App\Repositories\UserAttrRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;

class UserAttrService
{
    /* 前綴 */
    const KEY_PREFIX = 'ua';

    /* 存活時間 */
    const REDIS_TTL = 7200;

    protected $userAttrRepository;

    public function __construct(UserAttrRepository $userAttrRepository)
    {
        $this->userAttrRepository = $userAttrRepository;
    }

    public function get($k)
    {
        $key = sprintf("%s:%s:%d", self::KEY_PREFIX, $k, Auth::id());

        $v = Redis::get($key);
        if (empty($v)) {
            $v = $this->userAttrRepository->getVByK(Auth::id(), $k);
            Redis::setex($key, self::REDIS_TTL, $v);
        }

        return $v;
    }

    public function set($k, $v)
    {
        $key = sprintf("%s:%s:%d", self::KEY_PREFIX, $k, Auth::id());
        Redis::del($key);

        return $this->userAttrRepository->updateOrCreate(Auth::id(), $k, $v);
    }
}

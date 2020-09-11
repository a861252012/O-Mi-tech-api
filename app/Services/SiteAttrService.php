<?php
/**
 * 動態站點配置 服務
 * @author Weine
 * @date 2020-09-11
 */

namespace App\Services;


use App\Repositories\SiteAttrRepository;
use Illuminate\Support\Facades\Redis;

class SiteAttrService
{
    /* 前綴 */
    const KEY_PREFIX = 'sa';

    protected $siteAttrRepository;

    public function __construct(SiteAttrRepository  $siteAttrRepository)
    {
        $this->siteAttrRepository = $siteAttrRepository;
    }

    public function get($k, $siteId = 0)
    {
        $key = sprintf("%s:%s:%d", self::KEY_PREFIX, $k, $siteId);

        $v = Redis::get($key);
        if (empty($v)) {
            $v = $this->siteAttrRepository->getVByK($siteId, $k);
            Redis::set($key, $v);
        }

        return $v;
    }

    public function set($k, $v, $siteId = 0)
    {
        $key = sprintf("%s:%s:%d", self::KEY_PREFIX, $k, $siteId);
        Redis::del($key);

        return $this->siteAttrRepository->updateOrCreate($siteId, $k, $v);
    }
}
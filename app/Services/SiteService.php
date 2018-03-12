<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Redis;

/**
 * Created by PhpStorm.
 * User: nicholas
 * Date: 2018/3/8
 * Time: 15:22
 * @property Collection $siteDomain
 */
class SiteService
{
    const KEY_SITE_DOMAIN = 'hsite_domains:';
    protected $siteDomain;

    public function getDomainInfo()
    {
        if (isset($this->siteDomain)) {
            return $this->siteDomain;
        }
        $host = request()->getHost();
        $siteDomain = Redis::hgetall(SiteService::KEY_SITE_DOMAIN . $host);
        return $this->siteDomain = collect($siteDomain);
    }

    public function getSiteInfo()
    {
        
    }
}
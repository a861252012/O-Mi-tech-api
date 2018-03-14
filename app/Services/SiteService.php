<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\View;

/**
 * Created by PhpStorm.
 * User: nicholas
 * Date: 2018/3/8
 * Time: 15:22
 * @property Collection $domain
 * @property Collection $config
 */
class SiteService
{
    const KEY_SITE_DOMAIN = 'hsite_domains:';
    const KEY_SITE_CONFIG = 'hsite_config:';
    private $domain;
    private $config;
    private $host;
    private $isValid;
    private $msg;

    public function __construct()
    {

    }

    public function isValid(): bool
    {
        return $this->isValid;
    }

    public function fromRequest(\Illuminate\Http\Request $request): SiteService
    {
        $this->host = $request->getHost();
        $this->loadDomainInfo();
        list($isValid, $msg) = $this->checkValidity();
        $this->isValid = $isValid;
        $this->msg = $msg;
        return $this;
    }


    protected function loadDomainInfo(): void
    {
        $siteDomain = Redis::hgetall(static::KEY_SITE_DOMAIN . $this->host);
        $this->domain = collect($siteDomain ?: []);
    }

    public function domain(): Collection
    {
        if (!isset($this->domain)) {
            $this->loadDomainInfo();
        }
        return $this->domain;
    }

    public function siteId()
    {
        return $this->domain()->get('siteId');
    }

    protected function loadConfig(): void
    {
        $config = Redis::hGetAll(static::KEY_SITE_CONFIG . $this->siteId());
        $this->config = collect($config ?: []);
    }

    public function config(): Collection
    {
        if (!isset($config))
            $this->loadConfig();
        return $this->config;
    }

    public function checkValidity(): array
    {
        if (!$this->domain()->has('siteId')) {
            return [false, '域名配置错误，请联系客服！'];
        }
        if (!Redis::exists(static::KEY_SITE_CONFIG . $this->siteId())) {
            return [false, '站点配置缺失，请联系客服！'];
        }
        return [true, null];
    }

    public function shareConfigWithViews()
    {
        View::share('site',$this);
        View::share('cdn_host', $this->config()->get('cdn_host'));
        View::share('img_host', $this->config()->get('img_host'));
        View::share('open_web', $this->config()->get('open_web'));
        View::share('publish_version', $this->config()->get('publish_version'));
        View::share('flash_version', $this->config()->get('flash_version'));
        View::share('flash_version_h5', $this->config()->get('flash_version_h5'));
    }

    /**
     * @return mixed
     */
    public function getDomain()
    {
        return $this->domain;
    }

    public function getMsg()
    {
        return $this->msg;
    }

}
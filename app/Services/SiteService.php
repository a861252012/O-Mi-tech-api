<?php

namespace App\Services;

use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\View;
use Illuminate\Support\MessageBag;

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
    protected $booted;
    private $domain;
    private $config;
    private $host;
    private $errors;

    public function __construct()
    {
        $this->errors = new MessageBag();
    }

    public function isValid(): bool
    {
        return !$this->errors->any();
    }

    public function booted()
    {
        return $this->booted ?: false;
    }

    public function fromRequest(Request $request): SiteService
    {
        $this->booted = true;
        $this->host = $request->getHost();
        $this->loadDomainInfo();
        $this->checkDomainValidity($this->domain);
        $this->checkConfigValidity($this->siteId());
        return $this;
    }

    protected function loadDomainInfo(): void
    {
        $siteDomain = Redis::hgetall(static::KEY_SITE_DOMAIN . $this->host);
        $this->domain = collect($siteDomain);
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
        $config = $this->getConfigBySiteID($this->siteId());
        $this->config = collect($config);
    }

    /**
     * @param null $name
     * @return Collection|mixed
     */
    public function config($name = null)
    {
        if (is_null($this->config))
            $this->loadConfig();
        if (!is_null($name)) {
            return $this->config->get($name);
        }
        return $this->config;
    }

    public function checkDomainValidity(Collection $domain)
    {
        if (!$domain->has('siteId')) {
            $this->errors->add('domain', '域名配置错误，请联系客服！');
        }
    }

    public function checkConfigValidity(?int $siteId)
    {
        if (!Redis::exists(static::KEY_SITE_CONFIG . $siteId)) {
            $this->errors->add('config', '站点配置缺失，请联系客服！');
        }
    }

    public function shareConfigWithViews()
    {
        View::share('SiteSer', $this);
        View::share('cdn_host', $this->config()->get('cdn_host'));
        View::share('img_host', $this->config()->get('img_host'));
        View::share('open_web', $this->config()->get('open_web'));
        View::share('publish_version', $this->config()->get('publish_version'));
        View::share('flash_version', $this->config()->get('flash_version'));
        View::share('flash_version_h5', $this->config()->get('flash_version_h5'));
        View::share('public_path', $this->getPublicPath());
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

    public function getPublicPath()
    {
        return 's' . $this->siteId();
    }

    /**
     * @return MessageBag
     */
    public function errors(): MessageBag
    {
        return $this->errors;
    }

    public function getConfigBySiteID($id)
    {
        return Redis::hGetAll(static::KEY_SITE_CONFIG . $id);
    }

    public function getConfigArrayForSite(Site $site)
    {
        $config = $site->config;
        $configArray = array_combine($config->pluck('k')->toArray(), $config->pluck('v')->toArray());
        return $configArray;
    }

    public function flushConfigCacheForSite(Site $site)
    {
        return Redis::del(static::KEY_SITE_CONFIG . $site->id);
    }

    public function syncConfigForSite(Site $site)
    {
        $this->flushConfigCacheForSite($site);
        $configArray = $this->getConfigArrayForSite($site);
        return Redis::hMSet(static::KEY_SITE_CONFIG . $site->id , $configArray);
    }
}
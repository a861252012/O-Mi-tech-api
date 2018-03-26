<?php

namespace App\Services\Site;

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
 */
class SiteService
{
    const KEY_SITE_DOMAIN = 'hsite_domains:';
    const KEY_SITE_CONFIG = 'hsite_config:';
    protected $booted;
    /**
     * 当前域名信息，通过网络请求时才会有
     * @var Collection
     */
    private $domain;

    /**
     * 站点配置信息，懒加载
     * @var Collection
     */
    private $config;
    private $host;
    /**
     * @var MessageBag
     */
    private $errors;
    private $id;

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
        $this->checkConfigValidity($this->id);
        return $this;
    }

    public function fromID($id)
    {

    }

    protected function loadDomainInfo(): void
    {
        $siteDomain = Redis::hgetall(static::KEY_SITE_DOMAIN . $this->host);
        $this->domain = collect($siteDomain);
        if ($this->checkDomainValidity($this->domain)) {
            $this->id = $siteDomain['site_id'];
        }
    }

    public function domain(): Collection
    {
        return $this->domain;
    }

    public function siteId()
    {
        return $this->id;
    }

    protected function loadConfig(): void
    {
        $config = $this->getConfigBySiteID($this->siteId());
        $this->config = collect($config);
    }

    /**
     * 获取缓存，懒加载机制
     * @param null $name 如果没有name会
     * @param bool $noCache 跳过本地缓存
     * @return Collection
     */
    public function config($name = null,$noCache=true)
    {
        if (is_null($this->config))
            $this->loadConfig();
        if (!is_null($name)) {
            return $this->config->get($name);
        }
        return $this->config;
    }

    public function checkDomainValidity(Collection $domain): bool
    {
        if (!$domain->has('site_id')) {
            $this->errors->add('domain', '域名配置错误，请联系客服！');
            return false;
        }
        return true;
    }

    public function checkConfigValidity(?int $siteId): bool
    {
        if (!Redis::exists(static::KEY_SITE_CONFIG . $siteId)) {
            $this->errors->add('config', '站点配置缺失，请联系客服！');
            return false;
        }
        return true;
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
        return Redis::hMSet(static::KEY_SITE_CONFIG . $site->id, $configArray);
    }
}
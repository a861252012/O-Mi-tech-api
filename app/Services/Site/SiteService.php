<?php

namespace App\Services\Site;

use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\View;
use Illuminate\Support\MessageBag;
use RuntimeException;

/**
 * Created by PhpStorm.
 * User: nicholas
 * Date: 2018/3/8
 * Time: 15:22
 */
class SiteService
{
    const KEY_SITE_DOMAIN = 'hsite_domains:';

    /**
     * 当前域名信息，通过网络请求时才会有
     * @var Collection
     */
    private $domain;

    /**
     * 站点配置信息，懒加载
     * @var Config
     */
    private $config;

    /**
     * 请求域名
     * @var String
     */
    private $host;

    /**
     * @var MessageBag
     */
    private $errors;

    /**
     * 站点ID
     * @var int
     */
    private $id;

    public function __construct()
    {
        $this->errors = new MessageBag();
    }

    public function isValid(): bool
    {
        return !$this->errors->any();
    }

    public function fromRequest(Request $request): SiteService
    {
        $this->host = $request->getHttpHost();
        $this->loadDomainInfo();
        if (!is_null($this->id)) {
            $this->loadConfig();
            $this->checkConfigValidity();
        }
        return $this;
    }

    public function fromID($id)
    {
        if (is_null($id)) {
            throw new RuntimeException('site id cannot be null');
        }
        $this->id = $id;
        $this->loadConfig();
        $this->checkConfigValidity();
        return $this;
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
        $this->config = static::getConfigBySiteID($this->id);
    }

    public static function getConfigBySiteID($id)
    {
        return new Config($id);
    }

    /**
     * 获取缓存，懒加载机制
     * @param null $name    如果没有name会
     * @param bool $noCache 跳过本地缓存
     * @return Config
     */
    public function config($name = null, $noCache = true)
    {
        if (is_null($this->config))
            $this->loadConfig();
        if (!is_null($name)) {
            return $this->config->get($name, $noCache);
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

    public function checkConfigValidity(): bool
    {
        if (!$this->config()->isValid()) {
            $this->errors->add('config', '站点配置缺失，请联系客服！');
            return false;
        }
        return true;
    }

    public function shareConfigWithViews()
    {
        View::share('site', $this);
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

    public function getPublicPath()
    {
        return 's' . $this->id;
    }

    /**
     * @return MessageBag
     */
    public function errors(): MessageBag
    {
        return $this->errors;
    }

    public static function getDBConfigArrayForSite(Site $site)
    {
        $config = $site->config;
        $configArray = array_combine($config->pluck('k')->toArray(), $config->pluck('v')->toArray());
        return $configArray;
    }

    public static function flushConfigCacheForSite(Site $site)
    {
        return Config::flushByID($site->id);
    }

    public static function flushDomainCacheForSite(Site $site)
    {
        $redis = resolve('redis');
        $keys = $redis->keys(static::KEY_SITE_DOMAIN . '*');
        return collect($keys)->each(function ($key) use ($redis, $site) {
            if ($redis->hget($key, 'site_id') == $site->id) {
                $redis->del($key);
            }
        });
    }

    public static function syncConfigForSite(Site $site)
    {
        static::flushConfigCacheForSite($site);
        $configArray = static::getDBConfigArrayForSite($site);
        return Config::hMset($site->id, $configArray);
    }

    public static function syncDomainForSite(Site $site)
    {
        static::flushDomainCacheForSite($site);
        return resolve('redis')->pipeline(function ($pipe) use ($site) {
            $site->domains()->each(function ($domain) use ($pipe) {
                $pipe->hset(static::KEY_SITE_DOMAIN . $domain->domain, 'site_id', $domain->site_id);
            });
        });
    }

    public static function getIDs(): Collection
    {
        $keys = Redis::keys(Config::KEY_SITE_CONFIG . '*');
        return collect($keys)->map(function ($key) {
            return str_replace_first(Config::KEY_SITE_CONFIG, '', $key);
        });

    }
}
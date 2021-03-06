<?php

namespace App\Services\Site;

use App\Models\Site;
use App\Repositories\SiteConfigsRepository;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
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
    const KEY_SSITE_DOMAINS_NAME = 'ssite_domains_name';
    const KEY_SSITE_ID = 'ssite_id';
    const SITE_TOKEN_NAME = 'Site-Token';
    const SITE_TOKEN_LIFETIME_MINUTES = 5;

    /* APCU快取存活時間 */
    const APCU_TTL = 1;

    /**
     * 是否需要刷新site-token
     * @var bool
     */
    protected $siteTokenRefresh;
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

    public static function syncConfigForSite(Site $site)
    {
        $configArray = static::getDBConfigArrayForSite($site);
        static::flushConfigCacheForSite($site);
        return Config::hMset($site->id, $configArray);
    }

    public static function getDBConfigArrayForSite(Site $site)
    {
        $config = $site->config;
        $k = $config->pluck('k');
        $v = $config->pluck('v');
        $configArray = array_combine($k ? $k->toArray() : [], $v ? $v->toArray() : []);
        return $configArray;
    }

    public static function flushConfigCacheForSite(Site $site)
    {
        return Config::flushByID($site->id);
    }

    public static function syncDomainForSite(Site $site)
    {
        static::flushDomainCacheForSite($site);
        return resolve('redis')->pipeline(function ($pipe) use ($site) {
            $site->domains()->each(function ($domain) use ($pipe) {
                $pipe->hmset(static::KEY_SITE_DOMAIN . $domain->domain, $domain->toArray());
            });
        });
    }

    public static function flushDomainCacheForSite(Site $site)
    {
        $redis = resolve('redis');
        $keys = $redis->smembers(static::KEY_SSITE_DOMAINS_NAME);
        return collect($keys)->each(function ($key) use ($redis, $site) {
            if ($redis->hget(static::KEY_SITE_DOMAIN.$key, 'site_id') == $site->id) {
                $redis->del(static::KEY_SITE_DOMAIN.$key);
            }
        });
    }

    public static function getIDs(): Collection
    {
        $keys = Redis::smembers(static::KEY_SSITE_ID);
        return collect($keys);

    }

    public function siteTokenNeedsRefresh()
    {
        return $this->siteTokenRefresh;
    }

    public function isValid(): bool
    {
        return !$this->errors->any();
    }

    public function fromRequest(Request $request): SiteService
    {
        $this->host = $request->getHttpHost();
        if (!$this->validateSiteToken($request)) {
            $this->siteTokenRefresh = true;
            $this->loadDomainInfo();
        }
        if (!is_null($this->id)) {
            $this->loadConfig();
            $this->checkConfigValidity();
        }
        return $this;
    }

    protected function validateSiteToken(Request $request): bool
    {
        $siteToken = $this->getSiteTokenFromRequest($request);
        if (is_null($siteToken)) {
            return false;
        }
        try {
            list($host, $siteId, $created_at) = Crypt::decrypt($siteToken);
            if ($host !== $this->host || time() - $created_at >= static::SITE_TOKEN_LIFETIME_MINUTES * 60) {
                return false;
            }
            $this->host = $host;
            $this->id = $siteId;
        } catch (DecryptException $e) {
            return false;
        }
        return true;
    }

    public function getSiteTokenFromRequest(Request $request)
    {
        return $request->header(static::SITE_TOKEN_NAME) ?:
            $request->cookie(static::SITE_TOKEN_NAME);
    }

    /* 取得站點設置資訊 */
    protected function loadDomainInfo(): void
    {
        /* 由本機快取取得，如為null，則從redis取得並建立本機快取 */
        $siteDomain = Cache::get(static::KEY_SITE_DOMAIN . $this->host, function() {
            $data = Redis::hgetall(static::KEY_SITE_DOMAIN . $this->host);
            Cache::add(static::KEY_SITE_DOMAIN . $this->host, $data, self::APCU_TTL);

            if (empty($data)) {
                info("Redis Key: [" . static::KEY_SITE_DOMAIN . $this->host . "] 為空");
            }

            return $data;
        });

        $this->domain = collect($siteDomain);
        if ($this->checkDomainValidity($this->domain)) {
            $this->id = $siteDomain['site_id'];
        }
    }

    /* 檢查是否有站點ID */
    public function checkDomainValidity(Collection $domain): bool
    {
        if (!$domain->has('site_id')) {
            $this->errors->add('domain', __('messages.SiteService.check_domain'));
            return false;
        }

        return true;
    }

    protected function loadConfig(): void
    {
        $this->config = static::getConfigBySiteID($this->id);
    }

    public static function getConfigBySiteID($id)
    {
        return new Config($id);
    }

    public function checkConfigValidity(): bool
    {
        if (!$this->config()->isValid()) {
            $this->errors->add('config', __('messages.SiteService.check_site_config'));
            return false;
        }
        return true;
    }

    /**
     * 获取缓存，懒加载机制
     * @param null $name 如果没有name会
     * @param bool $noCache 跳过本地缓存
     * @return Config
     */
    public function config($name = null, $noCache = true)
    {
        /* 使用apcu快取的key */
        $apcuKeys = collect(['cdn_host', 'api_host', 'img_host', 'open_web', 'publish_version', 'down_url']);

        if (is_null($this->config))
        {
            $this->loadConfig();
            $apcuKeys->each(function($item) {
                Cache::add("sc:{$item}:{$this->host}", $this->config->get($item) ?? '', self::APCU_TTL);
            });
        }

        if (!is_null($name)) {
            return Cache::get("sc:{$name}:{$this->host}", function() use($name, $noCache, $apcuKeys) {
                $data = $this->config->get($name) ?? '';

                if ($apcuKeys->contains($name)) {
                    Cache::add("sc:{$name}:{$this->host}", $data, self::APCU_TTL);
                }

                return $data;
            });
        }

        return $this->config;
    }

    /**
     * 获取不分站点的设置
     * @param string $name
     * @return String
     */
    public function globalSiteConfig($name)
    {
        return Cache::get("sc:{$name}", function () use ($name) {
            $data = $this->siteConfig($name, $site_id = 0);
            Cache::add("sc:{$name}", $data, self::APCU_TTL);
            return $data;
        });
    }

    public function siteConfig($name, $site_id = null)
    {
        $redisKey = $this->siteConfigKey($name, $site_id);
        $v = Redis::get($redisKey);
        if ($v !== null) {
            return $v;
        }

        // read from db
        $siteConfigsRepository = resolve(SiteConfigsRepository::class);
        $v = $siteConfigsRepository->get($name, $site_id);
        if ($v === null) {
            Log::warn("資料庫無法取得設定, name: {$name}, site: {$site_id}");
            return null;
        }
        // set cache
        Redis::set($redisKey, $v->v);
        return $v->v;
    }

    private function siteConfigKey($k, $site_id = null)
    {
        // 有區分站點的設定才加上 :{site_id}
        // 預設不帶 site_id，以 session 内的站點来分
        $key = 'sc:'. $k;
        if ($site_id === null) {
            $site_id = $this->id;
        }
        if ($site_id > 0) {
            $key .= ':'. $site_id;
        }

        return $key;
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

    public function siteId()
    {
        return $this->id;
    }

    public function shareConfigWithViews()
    {
        View::share('site', $this);
        View::share('cdn_host', $this->config('cdn_host'));
        View::share('img_host', $this->config('img_host'));
        View::share('open_web', $this->config('open_web'));
        View::share('publish_version', $this->config('publish_version'));
        View::share('public_path', $this->getPublicPath());

        //下载地址（Young添加, 用于promo页面直接获取下载地址）
        View::share('down_url', $this->config('down_url'));
    }

    public function getPublicPath()
    {
        return 's' . $this->id;
    }

    /**
     * @return mixed
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * @return MessageBag
     */
    public function errors(): MessageBag
    {
        return $this->errors;
    }

    public function genSiteToken(): string
    {
        $host = $this->host;
        $siteId = $this->id;
        $created_at = time();
        $token = Crypt::encrypt([$host, $siteId, $created_at]);
        return $token;
    }

    public function domain(): Collection
    {
        return $this->domain;
    }
}
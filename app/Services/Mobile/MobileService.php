<?php
/**
 * Created by PhpStorm.
 * User: raby
 * Date: 2018/3/27
 * Time: 8:49
 */

namespace App\Services\Mobile;

use App\Models\AppVersion;
use App\Models\AppVersionIOS;
use App\Repositories\AppVersionRepository;
use App\Repositories\AppVersionIOSRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use App\Facades\SiteSer;

class MobileService
{
    const IOS = 1001;
    const ANDROID = 1002;
    const WEB = 1003;
    const APCU_TTL = 10;

    protected $appVersionRepository;

    public function __construct(
        AppVersionRepository $appVersionRepository,
        AppVersionIOSRepository $appVersionIOSRepository
    ) {
        $this->appVersionRepository = $appVersionRepository;
        $this->appVersionIOSRepository = $appVersionIOSRepository;
    }

    /**
     * @param string $branch
     * @return AppVersionIOS
     */
    public function getLastIosVersion($verCode = "", $branch = ""): AppVersionIOS
    {
        $nowSiteId = SiteSer::siteId();
        $verKey = 'm:app:versionsIOS:branch:' . $branch . ':' . $nowSiteId;

        $version = Redis::get($verKey);

        $rs = (new AppVersionIOS);

        if (!$version) {
            $version = $this->appVersionIOSRepository->getLastest($nowSiteId, $branch);
            if ($version) {
                Redis::set($verKey, json_encode($version));
                Redis::expire($verKey, 300);
                $rs = $version;
            }
        } else {
            $rs = $rs->forceFill(json_decode($version, true));
        }
        return $rs;
    }

    public function checkIos()
    {
        return self::IOS == $this->getClient();
    }

    public function checkAndroid()
    {
        return self::ANDROID == $this->getClient();
    }

    public function getClient()
    {
        return app('request')->header('client');
    }

    public function getJwt()
    {
        return app('request')->header('jwt');
    }

    public function getAgent()
    {
        return app('request')->header('agent');
    }

    public function getLastAndroidVersion($verCode = "", $branch = ""): AppVersion
    {
        $nowSiteId = SiteSer::siteId();
        $verKey = 'm:app:versions:branch:' . $branch . ':' . $nowSiteId;

        $version = Redis::get($verKey);

        $rs = (new AppVersion);
        if (!$version) {
            $version = $this->appVersionRepository->getLastest($nowSiteId, $branch);
            if ($version) {
                Redis::set($verKey, $version->toJson());
                Redis::expire($verKey, 300);
                $rs = $version;
            }
        } else {
            $rs->forceFill(json_decode($version, true));
        }
        return $rs;
    }

    /* 客戶端版是否需要強制更新(快取) */
    public function checkForceUpdate($verCode, $branch = "")
    {
        /* 如果低於一個版本，則強更 */
        if ($verCode < 21500) {
            return 1;
        }

        $updateVerKey = 'android:' . $verCode;

        return Cache::remember($updateVerKey, self::APCU_TTL, function () use ($verCode, $branch) {
            if (empty($this->appVersionRepository->getMandatoryCount(SiteSer::siteId(), (int)$verCode, $branch))) {
                return 0;
            }

            return 1;
        });
    }

    /* iOS客戶端版是否需要強制更新(快取) */
    public function checkIOSForceUpdate($verCode, $branch = "")
    {
        /* 如果低於一個版本，則強更 */
        if ($verCode < 21500) {
            return 1;
        }

        $updateVerKey = 'iOS:' . $verCode;

        return Cache::remember($updateVerKey, self::APCU_TTL, function () use ($verCode, $branch) {
            if (empty($this->appVersionIOSRepository->getMandatoryCount(SiteSer::siteId(), (int)$verCode, $branch))) {
                return 0;
            }

            return 1;
        });
    }
}
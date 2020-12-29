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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use App\Facades\SiteSer;

class MobileService
{
    const IOS = 1001;
    const ANDROID = 1002;
    const WEB = 1003;
    const APCU_TTL = 10;
    const SOCIAL_GROUP_LV_LIST = [
        0 => [1],
        1 => [2, 3, 4, 5, 6, 7, 8],
        2 => [9, 10, 11, 12, 13, 14],
        3 => [15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32, 33]
    ];

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
    public function getLastIosVersion($verCode = "", $branch = "")
    {
        $nowSiteId = SiteSer::siteId();
        $verKey = 'm:app:versionsIOS:branch:' . $branch . ':' . $nowSiteId;
        $updateVerKey = 'iOS:' . $verCode . ':' . $nowSiteId;
//        dd(Cache::forget($updateVerKey));

        Log::debug("取得APCU快取資訊($updateVerKey): " . json_encode(Cache::get($updateVerKey)));

        return Cache::remember($updateVerKey, self::APCU_TTL, function () use ($nowSiteId, $verCode, $branch, $verKey) {
            $version = collect(json_decode(Redis::get($verKey), true));
            if ($version->isEmpty()) {
                $version = $this->appVersionIOSRepository->getLastest($nowSiteId, $branch);
                if ($version) {
                    Log::debug("資料寫入Redis($verKey): " . $version->toJson());
                    Redis::set($verKey, $version->toJson());
                    Redis::expire($verKey, 300);
                } else {
                    return [];
                }
            }

            $v = $version->toArray();
            Log::debug('檢查是否需要強制更新');
            $v['mandatory'] = $this->checkIOSForceUpdate($verCode, $branch);
            Log::debug("資料寫入APCU: " . json_encode($v));

            return $v;
        });
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

    public function getLastAndroidVersion($verCode = "", $branch = "")
    {
        $nowSiteId = SiteSer::siteId();
        $verKey = 'm:app:versions:branch:' . $branch . ':' . $nowSiteId;
        $updateVerKey = 'android:' . $verCode . ':' . $branch . ':' . $nowSiteId;
//        dd(Cache::forget($updateVerKey));

        Log::debug("取得APCU快取資訊($updateVerKey): " . json_encode(Cache::get($updateVerKey)));

        return Cache::remember($updateVerKey, self::APCU_TTL, function () use ($nowSiteId, $verCode, $branch, $verKey) {
            $version = collect(json_decode(Redis::get($verKey), true));
            if ($version->isEmpty()) {
                $version = $this->appVersionRepository->getLastest($nowSiteId, $branch);
                if ($version) {
                    Log::debug("資料寫入Redis($verKey): " . $version->toJson());
                    Redis::set($verKey, $version->toJson());
                    Redis::expire($verKey, 300);
                } else {
                    return [];
                }
            }

            $v = $version->toArray();
            Log::debug('檢查是否需要強制更新');
            $v['mandatory'] = $this->checkForceUpdate($verCode, $branch);
            Log::debug("資料寫入APCU: " . json_encode($v));

            return $v;
        });
    }

    /* 客戶端版是否需要強制更新規則 */
    public function checkForceUpdate($verCode, $branch = "")
    {
        /* 如果低於一個版本，則強更 */
        if ($verCode < 21500) {
            return 1;
        }

        if (empty($this->appVersionRepository->getMandatoryCount(SiteSer::siteId(), (int)$verCode, $branch))) {
            return 0;
        }

        return 1;
    }

    /* iOS客戶端版是否需要強制更新(快取) */
    public function checkIOSForceUpdate($verCode, $branch = "")
    {
        /* 如果低於一個版本，則強更 */
        if ($verCode < 21500) {
            return 1;
        }

        if (empty($this->appVersionIOSRepository->getMandatoryCount(SiteSer::siteId(), (int)$verCode, $branch))) {
            return 0;
        }

        return 1;
    }

    public function getSocialGroupInfo($lvRich)
    {
        foreach (self::SOCIAL_GROUP_LV_LIST as $k => $v) {
            if (in_array($lvRich, $v)) {
                $socialGroupSetting = json_decode(SiteSer::globalSiteConfig('social_group_setting'));

                return $socialGroupSetting[$k];
            }
        }
        return [];
    }
}

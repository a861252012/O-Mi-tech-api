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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use App\Facades\SiteSer;

class MobileService
{
    const IOS = 1001;
    const ANDROID = 1002;
    const WEB = 1003;

    /**
     * @param string $branch
     * @return AppVersionIOS
     */
    public function getLastIosVersion($branch = ""): AppVersionIOS
    {
        $nowSiteId = SiteSer::siteId();
        $version = Redis::get('m:app:versionsIOS:branch:' . $branch . ':' . $nowSiteId);
        $rs = (new AppVersionIOS);
        if (!$version) {
            $version = AppVersionIOS::whereRaw('released_at<=now()')
                ->where('site_id',$nowSiteId)->where('branch', $branch)->whereNull('deleted_at')->orderBy('ver_code', 'desc')->first();
            if ($version) {
                Redis::set('m:app:versions:branchIOS:' . $branch . ':' . $nowSiteId, json_encode($version), 300);
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

    public function getLastAndroidVersion($branch = ""): AppVersion
    {
        $nowSiteId = SiteSer::siteId();
        $version = Redis::get('m:app:versions:branch:' . $branch . ':' . $nowSiteId);

        $rs = (new AppVersion);
        if (!$version) {
            $version = AppVersion::whereRaw('released_at<=now()')
                ->where('site_id', $nowSiteId)->where('branch', $branch)->whereNull('deleted_at')->orderBy('ver_code', 'desc')->first();
            if ($version) {
                Redis::set('m:app:versions:branch:' . $branch . ':' . $nowSiteId, json_encode($version), 300);
                $rs = $version;
            }
        } else {
            $rs = (new AppVersion)->forceFill(json_decode($version, true));
        }
        return $rs;
    }
}
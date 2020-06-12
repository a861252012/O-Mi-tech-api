<?php
/**
 * 版號 資源庫
 * @author Weine
 * @date 2020-06-09
 */

namespace App\Repositories;


use App\Models\AppVersion;

class AppVersionRepository
{
    protected $appVersion;

    public function __construct(AppVersion $appVersion)
    {
        $this->appVersion  =$appVersion;
    }

    public function getLastest($siteId, $branch)
    {
        return $this->appVersion->whereRaw('released_at<=now()')
            ->where('site_id', $siteId)
            ->where('branch', $branch)
            ->whereNull('deleted_at')
            ->orderBy('ver_code', 'desc')
            ->first();
    }

    public function getMandatoryCount($siteId, $verCode, $branch)
    {
        return $this->appVersion->where('ver_code', '>', $verCode)
            ->where('branch', $branch)
            ->where('site_id', $siteId)
            ->whereNull('deleted_at')
            ->where('mandatory', 1)
            ->count();
    }
}
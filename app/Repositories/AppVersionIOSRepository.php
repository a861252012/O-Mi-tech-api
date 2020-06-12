<?php

namespace App\Repositories;

use App\Models\AppVersionIOS;

class AppVersionIOSRepository
{
    protected $appVersionIOS;

    public function __construct(AppVersionIOS $appVersionIOS)
    {
        $this->appVersionIOS = $appVersionIOS;
    }

    public function getLastest($siteId, $branch)
    {
        return $this->appVersionIOS->whereRaw('released_at<=now()')
            ->where('site_id', $siteId)
            ->where('branch', $branch)
            ->whereNull('deleted_at')
            ->orderBy('ver_code', 'desc')
            ->first();
    }

    public function getMandatoryCount($siteId, $verCode, $branch)
    {
        return $this->appVersionIOS->where('ver_code', '>', $verCode)
            ->where('branch', $branch)
            ->where('site_id', $siteId)
            ->whereNull('deleted_at')
            ->where('mandatory', 1)
            ->count();
    }

}

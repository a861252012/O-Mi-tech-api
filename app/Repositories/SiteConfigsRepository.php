<?php
/**
 * 網站設定 資源庫
 * @author Weine
 * @date 2019/11/20
 */

namespace App\Repositories;

use App\Entities\SiteConfigs;
use App\Facades\SiteSer;

class SiteConfigsRepository
{
    protected $siteConfigs;

    public function __construct(SiteConfigs $siteConfigs)
    {
        $this->siteConfigs = $siteConfigs;
    }

    public function getByCondition($where)
    {
        return $this->siteConfigs->where($where)->first();
    }

    public function getSettingByHQT()
    {
        return $this->siteConfigs->where('site_id', SiteSer::siteId())
                    ->whereIn('k', ['hqt_game_status', 'hqt_marquee', 'hqt_game_setting'])
                    ->get()
                    ->mapWithKeys(function ($item) {
                        return [$item['k'] => $item['v']];
                    })
                    ->all();
    }

    public function get($name, $site_id = null)
    {
        if ($site_id === null) {
            $site_id = SiteSer::siteId();
        }
        return $this->siteConfigs
            ->where('site_id', $site_id)
            ->where('k', $name)
            ->first();
    }
}

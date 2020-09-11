<?php
/**
 * 動態站點配置 資源庫
 * @author Weine
 * @date 2020-9-11
 */

namespace App\Repositories;


use App\Entities\SiteAttr;

class SiteAttrRepository
{
    protected $siteAttr;

    public function __construct(SiteAttr $siteAttr)
    {
        $this->siteAttr = $siteAttr;
    }

    public function getVByK($siteId, $k)
    {
        return $this->siteAttr->where('site_id', $siteId)->where('k', $k)->value('v');
    }

    public function updateOrCreate($siteId, $k, $v)
    {
        return $this->siteAttr->updateOrCreate(
            ['site_id' => $siteId, 'k' => $k],
            ['v' => $v]
        );
    }
}
<?php

namespace App\Traits;

use App\Facades\SiteSer;
use App\Scopes\SiteScope;
use Illuminate\Database\Eloquent\Model;

/**
 * Created by PhpStorm.
 * User: nicholas
 * Date: 2018/4/19
 * Time: 14:05
 */
trait SiteSpecific
{
    public static function bootSiteSpecific()
    {
        static::addGlobalScope(new SiteScope);

        if (php_sapi_name() !== 'cli') {
            static::creating(function (Model $model) {
                $model->{$model->getSiteIdColumn()} = SiteSer::siteId() ?: 0;
            });
        }
    }

    public static function getSiteIdColumn()
    {
        return 'site_id';
    }
}
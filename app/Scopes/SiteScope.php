<?php
/**
 * Created by PhpStorm.
 * User: nicholas
 * Date: 2018/4/19
 * Time: 14:07
 */

namespace App\Scopes;


use App\Facades\SiteSer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class SiteScope implements Scope
{

    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $builder
     * @param  \Illuminate\Database\Eloquent\Model   $model
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        $builder->macro('allSites', function (Builder $builder) {
            return $builder->withoutGlobalScope($this);
        });
        if (php_sapi_name() === 'cli') {
            return;
        }
        $key_site_id = $model->getSiteIdColumn();
        $builder->where($key_site_id, SiteSer::siteId() ?: 0);
    }

    public function extend(Builder $builder)
    {
        $builder->macro('allSites', function (Builder $builder) {
            return $builder->withoutGlobalScope($this);
        });
    }
}
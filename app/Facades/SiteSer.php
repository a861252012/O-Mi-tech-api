<?php
/**
 * Created by PhpStorm.
 * User: nicholas
 * Date: 2018/3/21
 * Time: 11:27
 */

namespace App\Facades;


use App\Services\SiteService;
use Illuminate\Support\Facades\Facade;

class SiteSer extends Facade
{
    protected static function getFacadeAccessor()
    {
        return SiteService::class;
    }
}
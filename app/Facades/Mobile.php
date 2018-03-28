<?php
/**
 * Created by PhpStorm.
 * User: nicholas
 * Date: 2018/3/21
 * Time: 11:27
 */

namespace App\Facades;


use App\Services\Mobile\MobileService;
use Illuminate\Support\Facades\Facade;

class Mobile extends Facade
{
    protected static function getFacadeAccessor()
    {
        return MobileService::class;
    }
}
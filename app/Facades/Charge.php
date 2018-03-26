<?php
/**
 * Created by PhpStorm.
 * User: nicholas
 * Date: 2018/3/21
 * Time: 11:27
 */

namespace App\Facades;


use App\Services\Charge\ChargeService;
use Illuminate\Support\Facades\Facade;

class Charge extends Facade
{
    protected static function getFacadeAccessor()
    {
        return ChargeService::class;
    }
}
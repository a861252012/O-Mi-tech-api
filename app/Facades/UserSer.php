<?php
/**
 * Created by PhpStorm.
 * User: nicholas
 * Date: 2018/3/21
 * Time: 11:27
 */

namespace App\Facades;


use App\Services\User\UserService;
use Illuminate\Support\Facades\Facade;

class UserSer extends Facade
{
    protected static function getFacadeAccessor()
    {
        return UserService::class;
    }
}
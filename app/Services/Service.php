<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;
use Illuminate\Container\Container;

/**
 * 服务基类
 *
 * Class Service
 * @package Core
 */
class Service
{
    /**
     * 容器注入
     * @var
     */
    protected $container;
    public function __construct(Container $container)
    {
        $this->container = app();
    }


    /**
     * @param $name [注入名称]
     * @return mixed
     * @author dc
     * @version 20151022
     * @description 继承原注入方法,方便调用
     */
    protected function make($name){
        return Redis::resolve();
    }
}
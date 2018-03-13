<?php
namespace App\Services\Task\GiftScript;

use App\Models\LevelRich;

use Illuminate\Container\Container;
class GiftBase
{

    protected $_redisInstance;
    protected $config;

    protected $container;

    /**
     * GiftBase constructor.
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * 创建redis公共函数
     * @return mixed
     */
    protected function getredis(){
        return $this->container->make('redis');
    }

    /**
     * 获取等级对应的经验的值
     *
     * @return array
     */
    protected function getLvRich()
    {
        $lvs = LevelRich::where('type','member')->get();
        $data = array();
        foreach($lvs as $lv){
            $data[$lv['level_id']] = $lv;
        }
        return $data;
    }
}
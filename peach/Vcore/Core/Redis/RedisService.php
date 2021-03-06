<?php
namespace Core\Redis;

use Illuminate\Support\Arr;

/**
 * 使用php redis扩展-redis.so
 * @author liguoyi
 *
 */
class RedisService
{
    /**
     * 所有配置redis实例
     *
     * @var array
     */
    protected $clients;
    /**
     * 连接超时时间
     * @var int
     */
    protected $timeOut;

    /**
     * 根据配置文件初始化client
     * @param  array  $servers
     * @param int  $timeOut
     * @return  void
     */
    public function __construct(array $servers = [], $timeOut = 5)
    {
        $cluster = Arr::pull($servers, 'cluster');

        $options = (array) Arr::pull($servers, 'options');
        $this->timeOut = Arr::pull($servers , 'timeout');
        $this->clients = $this->createClients($servers, $options);
    }

    /**
     * 创建 Redis.io client
     * @param  array  $servers
     * @param  array  $options
     * @return array
     * @throw exception
     */
    protected function createClients(array $servers, array $options = [])
    {
        $clients = [];
        try {
            foreach ($servers as $key_s => $server) {
                $redis = new \Redis;
                //长连接为pconnect,长连接要注意执行close关闭
                $func = Arr::get($server, 'persistent', false) ? 'pconnect' : 'connect';

                $redis->connect(Arr::get($server, 'host', ''), Arr::get($server, 'port'), $this->timeOut);
                //有配置密码的，进行auth操作
                if ($pwd = Arr::get($server, 'password', '')) {
                    $redis->auth($pwd);
                }

                $redis->select(Arr::get($server, 'database'));
                //设置redis的option,如Redis::OPT_SERIALIZER, Redis::SERIALIZER_NONE
                foreach ($options as $key => $val) {
                    $redis->setOption($key, $val);
                }

                $clients[$key_s] = $redis;
            }
        }catch(\Exception $e){
            throw new \Exception("connect redis error:".var_export($e->getMessage() , 1));
        }
        return $clients;
    }


    /**
     * 获取其他server实例
     *
     * @param  string  $name
     * @return \Predis\ClientInterface|null
     */
    public function connection($name = 'default')
    {
        return Arr::get($this->clients, $name ?: 'default');
    }

    /**
     * 返回当前所有redis实例
     * @param string $key
     * @return resource
     */
    public function getClient($key)
    {
        return $this->clients[$key];
    }

    /**
     * 执行redis操作命令
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function command($method, array $parameters = [])
    {

        return call_user_func_array([$this->clients['default'], $method], $parameters);
    }


    /**
     * 动态执行命令
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->command($method, $parameters);
    }
}
<?php
namespace Core\Redis;
/**
 * Created by PhpStorm.
 * User: nicholas
 * Date: 2017/3/31
 * Time: 14:44
 */
class RedisQueue
{
    protected $redis;
    protected $default;
    protected $expire = 1;

    /**
     * RedisQueue constructor.
     * @param \Redis $redis
     * @param string $default
     */
    public function __construct($redis, $default = 'queue:default')
    {
        $this->redis = $redis;
        $this->default = $default;
    }

    public static function create($redis, $default = 'queue:default')
    {
        return new static($redis, $default);
    }

    /**
     * @param string|null $queue
     * @return string|null
     */
    public function pop($queue = null)
    {
        $queue = $this->getQueue($queue);
        if (!is_null($this->expire)) {
            $this->migrateAllExpiredJobs($queue);
        }
        $job = $this->getConnection()->lpop($queue);
        if (!empty($job)) {
            $this->getConnection()->zadd($queue . ':reserved', time() + $this->expire, $job);
            return $job;
        }
        return null;
    }

    public function getQueue($queue = null)
    {
        return $queue ?: $this->default;
    }

    protected function migrateAllExpiredJobs($queue)
    {
        $this->migrateExpiredJobs($queue . ':reserved', $queue);
    }

    public function migrateExpiredJobs($from, $to)
    {
        $jobs = $this->getExpiredJobs(
            $this->getConnection(), $from, $time = time()
        );
        if (count($jobs) > 0) {
            $transaction = $this->getConnection()->multi();
            $this->removeExpiredJobs($transaction, $from, $time);

            $this->pushExpiredJobsOntoNewQueue($transaction, $to, $jobs);
            $transaction->exec();
        }
    }

    /**
     * @param \Redis $transaction
     * @param $from
     * @param $time
     * @return array
     */
    protected function getExpiredJobs($transaction, $from, $time)
    {
        return $transaction->zrangebyscore($from, '-inf', $time);
    }

    public function getConnection()
    {
        return $this->redis;
    }

    /**
     * @param \Redis $transaction
     * @param $from
     * @param $time
     */
    protected function removeExpiredJobs($transaction, $from, $time)
    {
        $transaction->zremrangebyscore($from, '-inf', $time);
    }

    /**
     * @param \Redis $transaction
     * @param $to
     * @param $jobs
     */
    protected function pushExpiredJobsOntoNewQueue($transaction, $to, $jobs)
    {
        call_user_func_array([$transaction, 'rpush'], array_merge([$to], $jobs));
    }

    /**
     * @param string $payload
     * @param int $attempts
     */
    public function release($payload, $attempts)
    {
        $this->push(json_decode($payload, true), ['attempts' => $attempts]);
    }

    /**
     * @param array $job
     * @param array $data
     * @param string $queue
     */
    public function push($job, $data = [], $queue = null)
    {
        $this->redis->rPush($this->getQueue($queue), $this->createPayload($job, $data));
    }

    public function createPayload($job, $data = [])
    {
        return json_encode(array_merge($job, ['attempts' => 1], $data), JSON_UNESCAPED_UNICODE);
    }

    public function deleteReserved($job)
    {
        $this->getConnection()->zrem($this->getQueue() . ':reserved', $job);
    }

    public function getExpire()
    {
        return $this->expire;
    }

    public function setExpire($seconds)
    {
        $this->expire = $seconds;
    }

    public function getConfig($name)
    {
        return $this->redis->hGet($this->getQueue() . ':config', $name);
    }

    public function setConfig($name, $value)
    {
        return $this->redis->hSet($this->getQueue() . ':config', $name, $value);
    }
}
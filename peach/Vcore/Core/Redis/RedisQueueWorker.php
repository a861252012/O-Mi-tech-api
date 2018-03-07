<?php
namespace Core\Redis;
/**
 * Created by PhpStorm.
 * User: nicholas
 * Date: 2017/4/3
 * Time: 9:48
 */
abstract class RedisQueueWorker
{
    /**
     * RedisQueueWorker constructor.
     * @param RedisQueue $queue
     * @param \Redis $redis
     */
    public function __construct(RedisQueue &$queue, &$redis)
    {
        $this->queue = $queue;
        $this->redis = $redis;
    }

    /**
     * Handle when job failed
     * @param $job
     */
    public function handleJobFailed(&$job)
    {
        $this->queue->deleteReserved($job);
        $this->writeQueueLog('job failed and removed:' . $job);
    }

    /**
     * Handel when exception thrown
     * @param String $job
     * @param \Exception $e
     * @internal param RedisQueue $queue
     */
    public function handleJobException(&$job, $e = null)
    {
        $this->queue->deleteReserved($job);
        $this->queue->release($job, json_decode($job, true)['attempts'] + 1);
        $this->writeQueueLog('job exception and push back to queue:' . $job . ' Exception: ' . $e ? $e->getMessage() : '');
//        usleep(1000);
    }

    abstract public function writeQueueLog($msg);

    /**
     * @return \Redis
     */
    public function getRedis()
    {
        return $this->redis;
    }

    /**
     * Execute the queue
     * @return mixed
     */
    abstract public function handle();
}
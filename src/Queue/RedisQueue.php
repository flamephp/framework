<?php

declare(strict_types=1);

namespace Flame\Queue;

use Flame\Cache\RedisCacheDriver;
use Flame\Log\Log;
use RedisException;

class RedisQueue extends RedisCacheDriver
{
    private string $queueName;

    public function __construct(array $options, string $queueName)
    {
        parent::__construct($options);

        $this->queueName = $queueName;
    }

    /**
     * 推送队列任务
     */
    public function push(JobInterface $job): string
    {
        try {
            return $this->handler->xadd($this->getRedisKey(), '*', [serialize($job)]);
        } catch (RedisException $e) {
            Log::error($e);

            return '';
        }
    }

    /**
     * 出队列
     */
    public function pull(int $count = 10): array
    {
        try {
            $queues = $this->handler->xrange($this->getRedisKey(), '-', '+', $count);
            $jobs = [];
            if (! empty($queues)) {
                foreach ($queues as $id => $job) {
                    $job = unserialize($job[0]);
                    if ($job instanceof JobInterface) {
                        $jobs[$id] = $job;
                    }
                }
            }

            return $jobs;
        } catch (RedisException $e) {
            Log::error($e);

            return [];
        }
    }

    /**
     * 删除消息
     */
    public function remove(array $ids): int
    {
        try {
            return $this->handler->xdel($this->getRedisKey(), $ids);
        } catch (RedisException $e) {
            Log::error($e);

            return 0;
        }
    }

    /**
     * 获取队列任务总数
     */
    public function count(): int
    {
        try {
            return $this->handler->xlen($this->getRedisKey());
        } catch (RedisException $e) {
            Log::error($e);

            return 0;
        }
    }

    /**
     * 返回redis存储的key名
     */
    protected function getRedisKey(): string
    {
        $name = empty($this->queueName) ? $this->getRedisDefaultKeyName() : $this->queueName;

        return 'queues:'.$name;
    }

    /**
     * 获取默认的key名
     */
    protected function getRedisDefaultKeyName(): string
    {
        return 'default';
    }
}

<?php

declare(strict_types=1);

namespace Flame\Cache;

use DateInterval;
use DateTime;
use DateTimeInterface;

abstract class CacheDriver
{
    /**
     * 缓存参数
     */
    protected array $options = [];

    /**
     * 获取有效期
     *
     * @param  int|DateTimeInterface|DateInterval  $expire 有效期
     */
    protected function getExpireTime($expire): int
    {
        if ($expire instanceof DateTimeInterface) {
            $expire = $expire->getTimestamp() - time();
        } elseif ($expire instanceof DateInterval) {
            $expire = DateTime::createFromFormat('U', (string) time())
                ->add($expire)
                ->format('U') - time();
        }

        return (int) $expire;
    }

    /**
     * 获取实际的缓存标识
     *
     * @param  string  $name 缓存名
     */
    public function getCacheKey(string $name): string
    {
        return $this->options['prefix'].$name;
    }

    /**
     * 序列化数据
     *
     * @param  mixed  $data 缓存数据
     */
    protected function serialize($data): string
    {
        if (is_numeric($data)) {
            return (string) $data;
        }

        $serialize = $this->options['serialize'][0] ?? 'serialize';

        return $serialize($data);
    }

    /**
     * 反序列化数据
     *
     * @param  string  $data 缓存数据
     * @return mixed
     */
    protected function unserialize($data)
    {
        if (is_numeric($data)) {
            return $data;
        }

        $unserialize = $this->options['serialize'][1] ?? 'unserialize';

        return $unserialize($data);
    }
}

<?php

declare(strict_types=1);

namespace Flame\Cache;

use BadFunctionCallException;
use Redis;
use RedisException;

class RedisCacheDriver extends CacheDriver implements CacheInterface
{
    protected Redis $handler;

    /**
     * 配置参数
     */
    protected array $options = [
        'host' => '127.0.0.1',
        'port' => 6379,
        'password' => '',
        'database' => 0,
        'timeout' => 0,
        'expire' => 0,
        'persistent' => false,
        'prefix' => '',
        'tag_prefix' => 'tag:',
        'serialize' => [],
    ];

    /**
     * @throws RedisException
     */
    public function __construct(array $options = [])
    {
        if (! empty($options)) {
            $this->options = array_merge($this->options, $options);
        }

        if (extension_loaded('redis')) {
            $this->handler = new Redis;

            if ($this->options['persistent']) {
                $this->handler->pconnect($this->options['host'], (int) $this->options['port'], (int) $this->options['timeout'], 'persistent_id_'.$this->options['database']);
            } else {
                $this->handler->connect($this->options['host'], (int) $this->options['port'], (int) $this->options['timeout']);
            }

            if ('' != $this->options['password']) {
                $this->handler->auth($this->options['password']);
            }
        } else {
            throw new BadFunctionCallException('not support: redis');
        }

        if (0 != $this->options['database']) {
            $this->handler->select((int) $this->options['database']);
        }
    }

    /**
     * 判断缓存
     *
     * @param  string  $key 缓存变量名
     *
     * @throws RedisException
     */
    public function has(string $key): bool
    {
        return (bool) $this->handler->exists($this->getCacheKey($key));
    }

    /**
     * 读取缓存
     *
     * @param  string  $name 缓存变量名
     * @param  mixed  $default 默认值
     *
     * @throws RedisException
     */
    public function get(string $name, $default = null)
    {
        $key = $this->getCacheKey($name);
        $value = $this->handler->get($key);

        if (false === $value || is_null($value)) {
            return $default;
        }

        return $this->unserialize($value);
    }

    /**
     * 写入缓存
     *
     * @param  string  $name 缓存变量名
     * @param  mixed  $value 存储数据
     * @param  int|\DateTime  $expire 有效时间（秒）
     *
     * @throws RedisException
     */
    public function set(string $name, $value, $expire = null): bool
    {
        if (is_null($expire)) {
            $expire = $this->options['expire'];
        }

        $key = $this->getCacheKey($name);
        $expire = $this->getExpireTime($expire);
        $value = $this->serialize($value);

        if ($expire) {
            $this->handler->setex($key, $expire, $value);
        } else {
            $this->handler->set($key, $value);
        }

        return true;
    }

    /**
     * 自增缓存（针对数值缓存）
     *
     * @param  string  $name 缓存变量名
     * @param  int  $step 步长
     * @return false|int
     *
     * @throws RedisException
     */
    public function inc(string $name, int $step = 1)
    {
        $key = $this->getCacheKey($name);

        return $this->handler->incrby($key, $step);
    }

    /**
     * 自减缓存（针对数值缓存）
     *
     * @param  string  $name 缓存变量名
     * @param  int  $step 步长
     * @return false|int
     *
     * @throws RedisException
     */
    public function dec(string $name, int $step = 1)
    {
        $key = $this->getCacheKey($name);

        return $this->handler->decrby($key, $step);
    }

    /**
     * 删除缓存
     *
     * @param  string  $name 缓存变量名
     *
     * @throws RedisException
     */
    public function del(string $name): bool
    {
        $key = $this->getCacheKey($name);
        $result = $this->handler->del($key);

        return $result > 0;
    }

    /**
     * 清除缓存
     *
     * @throws RedisException
     */
    public function clear(): bool
    {
        $this->handler->flushDB();

        return true;
    }
}

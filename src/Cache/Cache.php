<?php

declare(strict_types=1);

namespace Flame\Cache;

use Exception;

/**
 * 缓存类
 */
class Cache
{
    /**
     * 缓存配置
     */
    protected array $config = [];

    /**
     * 配置名
     */
    protected string $cache = 'default';

    /**
     * 代理对象
     */
    public $proxyObj = null;

    /**
     * 代理缓存时间
     */
    public int $proxyExpire = 1800;

    /**
     * 缓存对象
     */
    protected static array $objArr = [];

    /**
     * 构建函数
     *
     * @throws Exception
     */
    public function __construct(string $cache = 'default')
    {
        if ($cache) {
            $this->cache = $cache;
        }
        $this->config = Config::get('cache.'.Config::get('cache.'.$this->cache));
        if (empty($this->config) || ! isset($this->config['cache_type'])) {
            throw new Exception($this->cache.' cache config error', 500);
        }
    }

    /**
     * 回调驱动
     *
     * @param  string  $method 回调方法
     * @param  array  $args 回调参数
     *
     * @throws Exception
     */
    public function __call(string $method, array $args)
    {
        if (! isset(self::$objArr[$this->cache])) {
            $cacheDriver = __NAMESPACE__.'\cache\\'.ucfirst($this->config['cache_type']).'Driver';
            if (! class_exists($cacheDriver)) {
                throw new Exception("Cache Driver '{$cacheDriver}' not found'", 500);
            }
            self::$objArr[$this->cache] = new $cacheDriver($this->config);
        }

        if ($this->proxyObj) { //proxy mode
            $key = md5(get_class($this->proxyObj).'_'.$method.'_'.var_export($args, true));
            $value = self::$objArr[$this->cache]->get($key);
            if (false === $value) {
                $value = call_user_func_array([$this->proxyObj, $method], $args);
                self::$objArr[$this->cache]->set($key, $value, $this->proxyExpire);
            }

            return $value;
        } else {
            return call_user_func_array([self::$objArr[$this->cache], $method], $args);
        }
    }
}

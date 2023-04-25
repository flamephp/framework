<?php

declare(strict_types=1);

namespace Flame\Cache;

interface CacheInterface
{
    /**
     * 判断缓存
     *
     * @param  string  $key 缓存变量名
     */
    public function has(string $key);

    /**
     * 获取缓存
     *
     * @param  string  $key 缓存名
     * @param  mixed  $default 默认缓存
     */
    public function get(string $key, $default);

    /**
     * 设置缓存
     *
     * @param  string  $key 缓存名
     * @param  mixed  $value 缓存内容
     * @param  int  $expire 缓存时间
     */
    public function set(string $key, $value, $expire = 1800);

    /**
     * 递增缓存值
     *
     * @param  string  $key 缓存名
     * @param  int  $value 递增数量
     * @return bool
     */
    public function inc(string $key, int $value = 1);

    /**
     * 递减缓存值
     *
     * @param  string  $key 缓存名
     * @param  int  $value 递增数量
     * @return bool
     */
    public function dec(string $key, int $value = 1);

    /**
     * 删除缓存
     */
    public function del(string $key): bool;

    /**
     * 清空缓存
     */
    public function clear(): bool;
}

<?php

declare(strict_types=1);

namespace Flame\Support;

abstract class Facade
{
    /**
     * 始终创建新的对象实例
     *
     * @var bool
     */
    protected static $alwaysNewInstance = true;

    protected static $instance;

    /**
     * 获取当前Facade对应类名
     */
    protected static function getFacadeClass(): string
    {
        return '';
    }

    /**
     * 创建Facade实例
     *
     * @static
     *
     * @return object
     */
    protected static function createFacade()
    {
        $class = static::getFacadeClass();

        if (static::$alwaysNewInstance) {
            return new $class();
        }

        if (! self::$instance) {
            self::$instance = new $class();
        }

        return self::$instance;
    }

    /**
     * 调用实际类的方法
     *
     * @return false|mixed
     */
    public static function __callStatic($method, $params)
    {
        return call_user_func_array([static::createFacade(), $method], $params);
    }
}

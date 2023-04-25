<?php

declare(strict_types=1);

namespace Flame\Foundation;

/**
 * 框架钩子类
 */
class Hook
{
    /**
     * 钩子列表
     */
    public static array $tags = [];

    /**
     * 初始化钩子
     */
    public static function init(string $basePath = ''): void
    {
        $basePath = empty($basePath) ? __DIR__ : rtrim($basePath, '/');
        $dir = str_replace('/', DIRECTORY_SEPARATOR, $basePath.'/Hook/');
        foreach (glob($dir.'*.php') as $file) {
            $pos = strrpos($file, DIRECTORY_SEPARATOR);
            if (false === $pos) {
                continue;
            }

            $class = substr($file, $pos + 1, -4);
            $class = "\\Flame\\Foundation\\Hook\\{$class}";

            $methods = get_class_methods($class);
            foreach ($methods as $method) {
                self::$tags[$method][] = $class;
            }
        }
    }

    /**
     * 执行钩子
     *
     * @param  string  $tag 钩子名
     * @param  array  $params 执行参数
     * @param  mixed|null  $result 钩子返回
     */
    public static function listen(string $tag, array $params = [], mixed &$result = null): bool
    {
        if (! isset(self::$tags[$tag])) {
            return false;
        }
        foreach (self::$tags[$tag] as $class) {
            $result = self::exec($class, $tag, $params);
            if (false === $result) {
                break;
            }
        }

        return true;
    }

    /**
     * 执行类
     *
     * @param  string  $class 类名
     * @param  string  $method 方法名
     * @param  array  $params 参数
     * @return object
     */
    protected static function exec($class, $method, $params)
    {
        static $objArr = [];
        if (! isset($objArr[$class])) {
            $objArr[$class] = new $class();
        }

        return call_user_func_array([$objArr[$class], $method], (array) $params);
    }
}

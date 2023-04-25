<?php

declare(strict_types=1);

namespace Flame\Config;

class Config
{
    /**
     * 全局配置
     */
    protected static array $config = [];

    /**
     * 初始化配置
     */
    public static function init(): void
    {
        $files = glob(config_path('*.php'));
        foreach ($files as $file) {
            $key = basename($file, '.php');
            self::$config[$key] = require $file;
        }
    }

    /**
     * 获取配置项
     *
     * @param  string  $key 配置名
     * @return mixed
     */
    public static function get($key = null)
    {
        if (empty($key)) {
            return self::$config;
        }
        $arr = explode('.', $key);
        switch (count($arr)) {
            case 1 :
                if (isset(self::$config[$arr[0]])) {
                    return self::$config[$arr[0]];
                }
                break;
            case 2 :
                if (isset(self::$config[$arr[0]][$arr[1]])) {
                    return self::$config[$arr[0]][$arr[1]];
                }
                break;
            case 3 :
                if (isset(self::$config[$arr[0]][$arr[1]][$arr[2]])) {
                    return self::$config[$arr[0]][$arr[1]][$arr[2]];
                }
                break;
            case 4 :
                if (isset(self::$config[$arr[0]][$arr[1]][$arr[2]][$arr[3]])) {
                    return self::$config[$arr[0]][$arr[1]][$arr[2][$arr[3]]];
                }
                break;
            case 5 :
                if (isset(self::$config[$arr[0]][$arr[1]][$arr[2]][$arr[3]][$arr[4]])) {
                    return self::$config[$arr[0]][$arr[1]][$arr[2][$arr[3]][$arr[4]]];
                }
                break;
            default:
                break;
        }

        return null;
    }

    /**
     * 设置配置项
     *
     * @param  string  $key 配置名
     * @param  mixed  $value 配置值
     */
    public static function set($key, $value)
    {
        $arr = explode('.', $key);
        switch (count($arr)) {
            case 1 :
                self::$config[$arr[0]] = $value;
                break;
            case 2 :
                self::$config[$arr[0]][$arr[1]] = $value;
                break;
            case 3 :
                self::$config[$arr[0]][$arr[1]][$arr[2]] = $value;
                break;
            case 4 :
                self::$config[$arr[0]][$arr[1]][$arr[2]][$arr[3]] = $value;
                break;
            case 5 :
                self::$config[$arr[0]][$arr[1]][$arr[2]][$arr[3]][$arr[4]] = $value;
                break;
            default:
                return false;
        }

        return true;
    }
}

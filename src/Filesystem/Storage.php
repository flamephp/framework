<?php

declare(strict_types=1);

namespace Flame\Filesystem;

use Exception;
use Flame\Config\Config;

/**
 * 文件存储类
 *
 * @method upload($object, $filePath)
 * @method read($object)
 * @method write($object, $content, $option)
 * @method append($object, $content)
 * @method delete($object)
 * @method isExists($object)
 * @method move($oldObject, $newObject)
 * @method url(string $url, int $timeout = 3600)
 * @method originUrl(string $url)
 */
class Storage
{
    /**
     * 存储配置
     */
    protected mixed $config = [];

    /**
     * 存储配置名
     */
    protected string $storage = 'default';

    /**
     * 驱动对象
     */
    protected static array $objArr = [];

    /**
     * 构建函数
     *
     * @param  string  $storage 存储配置名
     *
     * @throws Exception
     */
    public function __construct(string $storage = 'default')
    {
        if ($storage) {
            $this->storage = $storage;
        }
        $this->config = Config::get('filesystem.'.Config::get('filesystem.'.$this->storage));
        if (empty($this->config) || ! isset($this->config['storage_type'])) {
            throw new Exception($this->storage.' storage config error', 500);
        }
    }

    /**
     * 回调驱动
     *
     * @param  string  $method 回调方法
     * @param  array  $args 回调参数
     * @return object
     *
     * @throws Exception
     */
    public function __call(string $method, array $args)
    {
        if (! isset(self::$objArr[$this->storage])) {
            $storageDriver = __NAMESPACE__.'\\'.ucfirst($this->config['storage_type']).'Driver';
            if (! class_exists($storageDriver)) {
                throw new Exception("Storage Driver '{$storageDriver}' not found'", 500);
            }
            self::$objArr[$this->storage] = new $storageDriver($this->config);
        }

        return call_user_func_array([self::$objArr[$this->storage], $method], $args);
    }
}

<?php

declare(strict_types=1);

namespace Flame\Filesystem;

class FileDriver implements StorageInterface
{
    private array $config = [
        'root' => '',
        'asset_url' => '', // 公网可访问的自定义域名
    ];

    public function __construct(array $config)
    {
        $this->config = array_merge($this->config, $config);
    }

    public function upload($object, $filePath)
    {
        return rename($filePath, $this->absolutePath($object));
    }

    public function read($name)
    {
        return file_get_contents($this->absolutePath($name));
    }

    public function write($name, $content, $option = null)
    {
        return file_put_contents($this->absolutePath($name), $content, LOCK_EX);
    }

    public function append($name, $content)
    {
        return file_put_contents($this->absolutePath($name), $content, LOCK_EX | FILE_APPEND);
    }

    public function delete($name)
    {
        return @unlink($this->absolutePath($name));
    }

    public function isExists($name)
    {
        return file_exists($this->absolutePath($name));
    }

    public function move($oldName, $newName)
    {
        return rename($this->absolutePath($oldName), $this->absolutePath($newName));
    }

    public function url(string $url, int $timeout = 3600): string
    {
        return rtrim($this->config['asset_url'], '/').'/'.ltrim($url, '/');
    }

    private function absolutePath(string $name): string
    {
        return rtrim($this->config['root'], '/').'/'.ltrim($name, '/');
    }
}

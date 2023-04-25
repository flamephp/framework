<?php

declare(strict_types=1);

namespace Flame\Http;

use Closure;
use Exception;
use SplFileInfo;

class File extends SplFileInfo
{
    /**
     * 文件hash规则
     */
    protected array $hash = [];

    /**
     * hash文件名
     */
    protected string $hashName;

    /**
     * 保存的文件后缀
     */
    protected string $extension;

    /**
     * @throws Exception
     */
    public function __construct(string $path, bool $checkPath = true)
    {
        if ($checkPath && ! is_file($path)) {
            throw new Exception(sprintf('The file "%s" does not exist', $path));
        }

        parent::__construct($path);
    }

    /**
     * 获取文件的哈希散列值
     */
    public function hash(string $type = 'sha1'): string
    {
        if (! isset($this->hash[$type])) {
            $this->hash[$type] = hash_file($type, $this->getPathname());
        }

        return $this->hash[$type];
    }

    /**
     * 获取文件的MD5值
     */
    public function md5(): string
    {
        return $this->hash('md5');
    }

    /**
     * 获取文件的SHA1值
     */
    public function sha1(): string
    {
        return $this->hash('sha1');
    }

    /**
     * 获取文件类型信息
     */
    public function getMime(): string
    {
        $fInfo = finfo_open(FILEINFO_MIME_TYPE);

        return finfo_file($fInfo, $this->getPathname());
    }

    /**
     * 移动文件
     *
     * @throws Exception
     */
    public function move(string $directory, string $name = null): File
    {
        $target = $this->getTargetFile($directory, $name);

        set_error_handler(function ($type, $msg) use (&$error) {
            $error = $msg;
        });
        $renamed = rename($this->getPathname(), (string) $target);
        restore_error_handler();
        if (! $renamed) {
            throw new Exception(sprintf('Could not move the file "%s" to "%s" (%s)', $this->getPathname(), $target, strip_tags($error)));
        }

        @chmod((string) $target, 0666 & ~umask());

        return $target;
    }

    /**
     * 实例化一个新文件
     *
     * @throws Exception
     */
    protected function getTargetFile(string $directory, string $name = null): File
    {
        if (! is_dir($directory)) {
            if (false === @mkdir($directory, 0777, true) && ! is_dir($directory)) {
                throw new Exception(sprintf('Unable to create the "%s" directory', $directory));
            }
        } elseif (! is_writable($directory)) {
            throw new Exception(sprintf('Unable to write in the "%s" directory', $directory));
        }

        $target = rtrim($directory, '/\\').\DIRECTORY_SEPARATOR.(null === $name ? $this->getBasename() : $this->getName($name));

        return new self($target, false);
    }

    /**
     * 获取文件名
     */
    protected function getName(string $name): string
    {
        $originalName = str_replace('\\', '/', $name);
        $pos = strrpos($originalName, '/');

        return (false === $pos) ? $originalName : substr($originalName, $pos + 1);
    }

    /**
     * 文件扩展名
     */
    public function extension(): string
    {
        return $this->getExtension();
    }

    /**
     * 指定保存文件的扩展名
     */
    public function setExtension(string $extension): void
    {
        $this->extension = $extension;
    }

    /**
     * 自动生成文件名
     */
    public function hashName($rule = ''): string
    {
        if (! $this->hashName) {
            if ($rule instanceof Closure) {
                $this->hashName = call_user_func_array($rule, [$this]);
            } else {
                switch (true) {
                    case in_array($rule, hash_algos()):
                        $hash = $this->hash($rule);
                        $this->hashName = substr($hash, 0, 2).DIRECTORY_SEPARATOR.substr($hash, 2);
                        break;
                    case is_callable($rule):
                        $this->hashName = call_user_func($rule);
                        break;
                    default:
                        $this->hashName = date('Ymd').DIRECTORY_SEPARATOR.md5(microtime(true).$this->getPathname());
                        break;
                }
            }
        }

        $extension = $this->extension ?? $this->extension();

        return $this->hashName.($extension ? '.'.$extension : '');
    }
}

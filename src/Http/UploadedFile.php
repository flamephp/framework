<?php

declare(strict_types=1);

namespace Flame\Http;

use Exception;

class UploadedFile extends File
{
    private $test = false;

    private $originalName;

    private $mimeType;

    private $error;

    public function __construct(string $path, string $originalName, string $mimeType = null, int $error = null, bool $test = false)
    {
        $this->originalName = $originalName;
        $this->mimeType = $mimeType ?: 'application/octet-stream';
        $this->test = $test;
        $this->error = $error ?: UPLOAD_ERR_OK;

        parent::__construct($path, UPLOAD_ERR_OK === $this->error);
    }

    public function isValid(): bool
    {
        $isOk = UPLOAD_ERR_OK === $this->error;

        return $this->test ? $isOk : $isOk && is_uploaded_file($this->getPathname());
    }

    /**
     * 上传文件
     *
     * @param  string  $directory 保存路径
     * @param  string|null  $name 保存的文件名
     *
     * @throws Exception
     */
    public function move(string $directory, string $name = null): File
    {
        if ($this->isValid()) {
            if ($this->test) {
                return parent::move($directory, $name);
            }

            $target = $this->getTargetFile($directory, $name);

            set_error_handler(function ($type, $msg) use (&$error) {
                $error = $msg;
            });

            $moved = move_uploaded_file($this->getPathname(), (string) $target);
            restore_error_handler();
            if (! $moved) {
                throw new Exception(sprintf('Could not move the file "%s" to "%s" (%s)', $this->getPathname(), $target, strip_tags($error)));
            }

            @chmod((string) $target, 0666 & ~umask());

            return $target;
        }

        throw new Exception($this->getErrorMessage());
    }

    /**
     * 获取错误信息
     */
    protected function getErrorMessage(): string
    {
        return match ($this->error) {
            1, 2 => 'upload File size exceeds the maximum value',
            3 => 'only the portion of file is uploaded',
            4 => 'no file to uploaded',
            6 => 'upload temp dir not found',
            7 => 'file write error',
            default => 'unknown upload error',
        };
    }

    /**
     * 获取上传文件类型信息
     */
    public function getOriginalMime(): string
    {
        return $this->mimeType;
    }

    /**
     * 上传文件名
     */
    public function getOriginalName(): string
    {
        return $this->originalName;
    }

    /**
     * 获取上传文件扩展名
     */
    public function getOriginalExtension(): string
    {
        return pathinfo($this->originalName, PATHINFO_EXTENSION);
    }

    /**
     * 获取文件扩展名
     */
    public function extension(): string
    {
        return $this->getOriginalExtension();
    }
}

<?php

declare(strict_types=1);

namespace Flame\Filesystem;

/**
 * 文件存储驱动接口
 */
interface StorageInterface
{
    /**
     * 上传文件
     *
     * @return bool
     */
    public function upload($object, $filePath);

    /**
     * 读取文件
     *
     * @param  string  $object 文件名
     * @return string
     */
    public function read($object);

    /**
     * 写入文件
     *
     * @param  string  $object 文件名
     * @param  string  $content 文件内容
     * @param  array  $option 写入参数
     * @return bool
     */
    public function write($object, $content, $option);

    /**
     * 追加内容
     *
     * @param  string  $object 文件名
     * @param  string  $content 追加内容
     * @return bool
     */
    public function append($object, $content);

    /**
     * 删除文件
     *
     * @param  string  $object 文件名
     * @return bool
     */
    public function delete($object);

    /**
     * 判断文件存在
     *
     * @param  string  $object 文件名
     * @return bool
     */
    public function isExists($object);

    /**
     * 移动文件
     *
     * @param  string  $oldObject 原文件名/路径
     * @param  string  $newObject 新路径名/目录
     * @return bool
     */
    public function move($oldObject, $newObject);

    /**
     * 生成url链接
     */
    public function url(string $url, int $timeout): string;
}

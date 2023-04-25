<?php

declare(strict_types=1);

namespace Flame\Filesystem;

use OSS\Core\OssException;
use OSS\OssClient;

class AliOSSDriver implements StorageInterface
{
    private ?OssClient $ossClient = null;

    private array $config = [
        'access_key_id' => '', // OSS Key
        'access_key_secret' => '', // OSS Secret
        'bucket' => '', // OSS Bucket
        'endpoint' => '', // OSS所在区域的域名
        'asset_url' => '', // 公网可访问的自定义域名
    ];

    public function __construct(array $config)
    {
        $this->config = array_merge($this->config, $config);
    }

    private function instance(): OssClient
    {
        if (is_null($this->ossClient)) {
            $accessKeyId = $this->config['access_key_id'];
            $accessKeySecret = $this->config['access_key_secret'];
            $endpoint = $this->config['endpoint'];
            try {
                $this->ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
            } catch (OssException $e) {
                exit($e->getMessage());
            }
        }

        return $this->ossClient;
    }

    public function upload($object, $filePath)
    {
        try {
            return $this->instance()->uploadFile($this->config['bucket'], $object, $filePath);
        } catch (OssException $e) {
            exit($e->getMessage());
        }
    }

    public function read($object)
    {
        try {
            return $this->instance()->getObject($this->config['bucket'], $object);
        } catch (OssException $e) {
            exit($e->getMessage());
        }
    }

    public function write($object, $content, $option = null)
    {
        try {
            return $this->instance()->putObject($this->config['bucket'], $object, $content, $option);
        } catch (OssException $e) {
            exit($e->getMessage());
        }
    }

    public function append($object, $content)
    {
        try {
            // TODO check position args
            return $this->instance()->appendObject($this->config['bucket'], $object, $content, 0);
        } catch (OssException $e) {
            exit($e->getMessage());
        }
    }

    public function delete($object)
    {
        try {
            return $this->instance()->deleteObject($this->config['bucket'], $object);
        } catch (OssException $e) {
            exit($e->getMessage());
        }
    }

    public function isExists($object)
    {
        try {
            return $this->instance()->doesObjectExist($this->config['bucket'], $object);
        } catch (OssException $e) {
            exit($e->getMessage());
        }
    }

    public function move($oldObject, $newObject)
    {
        try {
            $this->instance()->copyObject($this->config['bucket'], $oldObject, $this->config['bucket'], $newObject);
            $this->instance()->deleteObject($this->config['bucket'], $oldObject);
        } catch (OssException $e) {
            exit($e->getMessage());
        }
    }

    /**
     * @throws OssException
     */
    public function url(string $url, int $timeout = 3600): string
    {
        return $this->instance()->signUrl($this->config['bucket'], ltrim($url, '/'), $timeout);
    }

    public function originUrl(string $signUrl): string
    {
        $parseUrls = parse_url($signUrl);

        return $parseUrls['path'] ? ltrim($parseUrls['path'], '/') : '';
    }
}

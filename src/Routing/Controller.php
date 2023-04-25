<?php

declare(strict_types=1);

namespace Flame\Routing;

use Flame\Http\File;
use Flame\Http\Request;
use Flame\Http\Response;
use Flame\Http\UploadedFile;
use Exception;
use Throwable;

/**
 * 公共控制器
 */
class Controller
{
    /**
     * 判断post提交
     */
    public function isPost(): bool
    {
        return Request::isPost();
    }

    /**
     * 判断get提交
     */
    public function isGet(): bool
    {
        return Request::isGet();
    }

    /**
     * 判断ajax提交
     */
    public function isAjax(): bool
    {
        try {
            return Request::isAjax();
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * 获取JSON请求数据
     */
    public function requestBody(): array
    {
        $data = json_decode(file_get_contents('php://input'), true);
        if (in_array(gettype($data), ['boolean', 'NULL'])) {
            return [];
        }

        return $data;
    }

    /**
     * 请求过滤post、get
     */
    public function input($name = null, $default = null)
    {
        static $args;
        if (! $args) {
            $args = array_merge($_GET, $_POST);
        }
        if (null == $name) {
            return $args;
        }
        if (! isset($args[$name])) {
            return $default;
        }
        $arg = $args[$name];
        if (is_array($arg)) {
            array_walk($arg, function (&$v) {
                $v = trim(htmlspecialchars($v, ENT_QUOTES, 'UTF-8'));
            });
        } else {
            $arg = trim(htmlspecialchars($arg, ENT_QUOTES, 'UTF-8'));
        }

        return $arg;
    }

    /**
     * 获取上传的文件信息
     *
     * @throws Exception
     */
    public function file(string $name = '')
    {
        $files = $_FILES;
        if (! empty($files)) {
            if (strpos($name, '.')) {
                [$name, $sub] = explode('.', $name);
            }

            // 处理上传文件
            $array = $this->dealUploadFile($files, $name);

            if ('' === $name) {
                // 获取全部文件
                return $array;
            } elseif (isset($sub) && isset($array[$name][$sub])) {
                return $array[$name][$sub];
            } elseif (isset($array[$name])) {
                return $array[$name];
            }
        }

        return null;
    }

    /**
     * 返回成功JSON数据
     */
    protected function success($data = null): Response
    {
        return Response::create([
            'code' => 0,
            'message' => 'ok',
            'data' => $data,
        ], 'json');
    }

    /**
     * 返回失败JSON数据
     */
    protected function fail($message = 'fail', $code = 400): Response
    {
        return Response::create([
            'code' => $code,
            'message' => $message,
            'data' => null,
        ], 'json');
    }

    /**
     * @throws Exception
     */
    protected function dealUploadFile(array $files, string $name): array
    {
        $array = [];
        foreach ($files as $key => $file) {
            if (is_array($file['name'])) {
                $item = [];
                $keys = array_keys($file);
                $count = count($file['name']);

                for ($i = 0; $i < $count; $i++) {
                    if ($file['error'][$i] > 0) {
                        if ($name == $key) {
                            $this->throwUploadFileError($file['error'][$i]);
                        } else {
                            continue;
                        }
                    }

                    $temp['key'] = $key;

                    foreach ($keys as $_key) {
                        $temp[$_key] = $file[$_key][$i];
                    }

                    $item[] = new UploadedFile($temp['tmp_name'], $temp['name'], $temp['type'], $temp['error']);
                }

                $array[$key] = $item;
            } else {
                if ($file instanceof File) {
                    $array[$key] = $file;
                } else {
                    if ($file['error'] > 0) {
                        if ($key == $name) {
                            $this->throwUploadFileError($file['error']);
                        } else {
                            continue;
                        }
                    }

                    $array[$key] = new UploadedFile($file['tmp_name'], $file['name'], $file['type'], $file['error']);
                }
            }
        }

        return $array;
    }

    /**
     * @throws Exception
     */
    protected function throwUploadFileError($error)
    {
        static $fileUploadErrors = [
            1 => 'upload File size exceeds the maximum value',
            2 => 'upload File size exceeds the maximum value',
            3 => 'only the portion of file is uploaded',
            4 => 'no file to uploaded',
            6 => 'upload temp dir not found',
            7 => 'file write error',
        ];

        throw new Exception($fileUploadErrors[$error], $error);
    }
}

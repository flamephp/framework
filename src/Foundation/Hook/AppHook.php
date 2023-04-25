<?php

declare(strict_types=1);

namespace Flame\Foundation\Hook;

use Flame\Http\Response;
use Exception;

/**
 * 框架启动钩子
 */
class AppHook
{
    /**
     * 开始时间
     */
    public float $startTime = 0;

    /**
     * 框架启动
     */
    public function appBegin(): void
    {
        $this->startTime = microtime(true);
    }

    /**
     * 框架结束
     */
    public function appEnd(): void
    {
        //echo microtime(true) - $this->startTime ;
    }

    /**
     * 框架错误
     *
     * @throws Exception
     */
    public function appError($e): void
    {
        if (404 == $e->getCode()) {
            $res = [
                'code' => 404,
                'message' => 'API Not Found.',
                'data' => null,
            ];
        } else {
            $res = [
                'code' => 500,
                'message' => 'Service Unavailable.',
                'data' => null,
            ];

            if (config('app.debug')) {
                $res['data'] = [
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTrace(),
                ];
            }
        }

        Response::create($res, 'json')->send();
        exit();
    }

    /**
     * 路由解析
     *
     * @param  array  $rewriteRule 路由规则
     * @param  bool  $rewriteOn 路由开关
     */
    public function routeParseUrl(array $rewriteRule, bool $rewriteOn): void
    {
    }

    /**
     * 方法开始
     *
     * @param  object  $obj 操作对象
     * @param  string  $action 方法名
     */
    public function actionBefore($obj, string $action): void
    {
    }

    /**
     * 方法结束
     *
     * @param  object  $obj 操作对象
     * @param  string  $action 方法名
     */
    public function actionAfter($obj, string $action): void
    {
    }
}

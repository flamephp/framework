<?php

declare(strict_types=1);

namespace Flame\Foundation;

use Flame\Config\Config;
use Flame\Facade\DB;
use Exception;
use Flame\Http\Response;
use Flame\Log\Log;
use Flame\Routing\Route;
use Throwable;

/**
 * 应用启动
 */
class App
{
    /**
     * 初始化配置
     */
    protected static function init(): void
    {
        Config::init();

        if (Config::get('app.debug')) {
            ini_set('display_errors', 1);
            error_reporting(E_ALL);
            set_error_handler(function ($errno, $errStr, $errFile, $errLine) {
                $message = "$errStr in $errFile on line $errLine";
                Response::create(['code' => 500, 'message' => $message, 'data' => null], 'json', 500)->send();
                Log::error($message);
                exit();
            });
        } else {
            ini_set('display_errors', 0);
            error_reporting(0);
        }

        DB::setConfig(Config::get('database'));
    }

    /**
     * 运行框架
     */
    public static function run(): void
    {
        try {
            self::init();

            Hook::init();
            Hook::listen('appBegin');

            Hook::listen('routeParseUrl', [Config::get('route.rewrite_rule'), Config::get('route.rewrite_on')]);

            //default route
            if (! defined('APP_NAME') || ! defined('CONTROLLER_NAME') || ! defined('ACTION_NAME')) {
                Route::parseUrl(Config::get('route.rewrite_rule'), Config::get('route.rewrite_on'));
            }

            //execute action
            $controller = '\\App\\Gateways\\'.APP_NAME.'\\Controllers\\'.parse_name(CONTROLLER_NAME, 1).'Controller';
            if (! class_exists($controller)) {
                throw new Exception("Controller '{$controller}' not found", 404);
            }
            $obj = new $controller();
            $action = parse_name(ACTION_NAME, 1, false);
            if (! method_exists($obj, $action)) {
                throw new Exception("Action '{$controller}::{$action}()' not found", 404);
            }

            Hook::listen('actionBefore', [$obj, $action]);
            $response = $obj->$action();
            Hook::listen('actionAfter', [$obj, $action]);
            if ($response instanceof Response) {
                $response->send();
            } else {
                echo $response;
            }
        } catch (Throwable $e) {
            Log::error($e);

            Hook::listen('appError', [$e]);
        }

        Hook::listen('appEnd');
    }
}

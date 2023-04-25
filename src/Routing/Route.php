<?php

declare(strict_types=1);

namespace Flame\Routing;

use Flame\Config\Config;
use Flame\Http\Request;
use Flame\Support\Str;

/**
 * 路由类
 */
class Route
{
    /**
     * 路由规则
     */
    protected static array $rewriteRule = [];

    /**
     * 路由开关
     */
    protected static bool $rewriteOn = false;

    /**
     * 解析URL
     */
    public static function parseUrl(array $rewriteRule, bool $rewriteOn = false): void
    {
        self::$rewriteRule = $rewriteRule;
        self::$rewriteOn = $rewriteOn;
        if (self::$rewriteOn && ! empty(self::$rewriteRule)) {
            if (($pos = strpos($_SERVER['REQUEST_URI'], '?')) !== false) {
                parse_str(substr($_SERVER['REQUEST_URI'], $pos + 1), $_GET);
            }

            if (Config::get('route.context_path')) {
                $_SERVER['REQUEST_URI'] = str_replace(Config::get('route.context_path'), '/', $_SERVER['REQUEST_URI']);
            }

            foreach (self::$rewriteRule as $rule => $mapper) {
                $rule = $_SERVER['HTTP_HOST'].rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\').'/'.$rule;
                $rule = '/'.str_ireplace(['-', '/', '<', '>', '.'], ['\-', '\/', '(?<', '>[a-z0-9_%]+)', '\.'], $rule).'/i';
                if (preg_match($rule, $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'], $matches)) {
                    foreach ($matches as $matchKey => $matchVal) {
                        if (('app' === $matchKey)) {
                            $mapper = str_ireplace('<app>', $matchVal, $mapper);
                        } elseif ('c' === $matchKey) {
                            $mapper = str_ireplace('<c>', $matchVal, $mapper);
                        } elseif ('a' === $matchKey) {
                            $mapper = str_ireplace('<a>', $matchVal, $mapper);
                        } else {
                            if (! is_int($matchKey)) {
                                $_GET[$matchKey] = $matchVal;
                            }
                        }
                    }
                    $_REQUEST['r'] = $mapper;
                    break;
                }
            }
        }

        $routeArr = isset($_REQUEST['r']) ? explode('/', $_REQUEST['r']) : [];
        $appName = empty($routeArr[0]) ? Config::get('route.default_app') : $routeArr[0];
        $controllerName = empty($routeArr[1]) ? Config::get('route.default_controller') : $routeArr[1];
        $actionName = empty($routeArr[2]) ? Config::get('route.default_action') : $routeArr[2];
        $_REQUEST['r'] = $appName.'/'.$controllerName.'/'.$actionName;

        if (! defined('APP_NAME')) {
            define('APP_NAME', Str::studly($appName));
        }
        if (! defined('CONTROLLER_NAME')) {
            define('CONTROLLER_NAME', $controllerName);
        }
        if (! defined('ACTION_NAME')) {
            define('ACTION_NAME', $actionName);
        }
    }

    /**
     * 生成URL
     */
    public static function url(string $route = '', array $params = []): string
    {
        $app = APP_NAME;
        $controller = CONTROLLER_NAME;
        $action = ACTION_NAME;
        if ($route) {
            $route = explode('/', $route, 3);
            $routeNum = count($route);
            switch ($routeNum) {
                case 1:
                    $action = $route[0];
                    break;
                case 2:
                    $controller = $route[0];
                    $action = $route[1];
                    break;
                case 3:
                    $app = $route[0];
                    $controller = $route[1];
                    $action = $route[2];
                    break;
            }
        }
        $route = $app.'/'.$controller.'/'.$action;
        $paramStr = empty($params) ? '' : '&'.http_build_query($params);
        $url = $_SERVER['SCRIPT_NAME'].'?r='.$route.$paramStr;

        if (self::$rewriteOn && ! empty(self::$rewriteRule)) {
            static $urlArray = [];
            if (! isset($urlArray[$url])) {
                foreach (self::$rewriteRule as $rule => $mapper) {
                    $mapper = '/'.str_ireplace(['/', '<app>', '<c>', '<a>'], ['\/', '(?<app>\w+)', '(?<c>\w+)', '(?<a>\w+)'], $mapper).'/i';
                    if (preg_match($mapper, $route, $matches)) {
                        [$app, $controller, $action] = explode('/', $route);
                        $urlArray[$url] = str_ireplace(['<app>', '<c>', '<a>'], [$app, $controller, $action], $rule);
                        if (! empty($params)) {
                            $_args = [];
                            foreach ($params as $argKey => $arg) {
                                $count = 0;
                                $urlArray[$url] = str_ireplace('<'.$argKey.'>', $arg, $urlArray[$url], $count);
                                if (! $count) {
                                    $_args[$argKey] = $arg;
                                }
                            }

                            if (! empty($_args)) {
                                $urlArray[$url] = preg_replace('/<\w+>/', '', $urlArray[$url]).'?'.http_build_query($_args);
                            }
                        }

                        if (false === stripos($urlArray[$url], Request::scheme().'://')) {
                            $urlArray[$url] = Request::scheme().'://'.$_SERVER['HTTP_HOST'].rtrim(dirname($_SERVER['SCRIPT_NAME']), './\\').'/'.ltrim($urlArray[$url], './\\');
                        }

                        $rule = str_ireplace(['<app>', '<c>', '<a>'], '', $rule);
                        if (count($params) == preg_match_all('/<\w+>/is', $rule, $_match)) {
                            return $urlArray[$url];
                        }
                    }
                }

                return $urlArray[$url] ?? $url;
            }

            return $urlArray[$url];
        }

        return $url;
    }
}

<?php

declare(strict_types=1);

use Flame\Cache\Cache;
use Flame\Config\Config;
use Flame\Routing\Route;
use Flame\Filesystem\Storage;
use Carbon\Carbon;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * 标准时间
 */
function now(): Carbon
{
    return Carbon::now();
}

/**
 * 断点打印
 */
function dd($data): void
{
    dump($data);
    exit;
}

/**
 * 调试打印
 */
function dump($data): void
{
    echo '<pre>';
    print_r($data);
    echo '</pre>';
}

/**
 * 获取操作系统
 */
function uname(): string
{
    if (strtoupper(mb_substr(PHP_OS, 0, 3)) === 'WIN') {
        return 'WIN';
    }

    return strtoupper(PHP_OS);
}

/**
 * 返回资源url链接
 *
 * @throws Exception
 */
function asset(string $url): string
{
    static $_storage;

    if (is_null($_storage)) {
        $_storage = new Storage();
    }

    return $_storage->url($url);
}

/**
 * 连接路径
 */
function join_paths($basePath, $path = ''): string
{
    return $basePath.($path != '' ? '/'.ltrim($path, '/') : '');
}

/**
 * 根目录
 */
function base_path(string $path = ''): string
{
    return join_paths(ROOT_PATH, $path);
}

/**
 * 应用目录
 */
function app_path(string $path = ''): string
{
    return join_paths(ROOT_PATH.'app', $path);
}

/**
 * 配置目录
 */
function config_path(string $path = ''): string
{
    return join_paths(ROOT_PATH.'config', $path);
}

/**
 * 数据库目录
 */
function database_path(string $path = ''): string
{
    return join_paths(ROOT_PATH.'database', $path);
}

/**
 * WWW目录
 */
function public_path(string $path = ''): string
{
    return join_paths(ROOT_PATH.'public', $path);
}

/**
 * 资源目录
 */
function resource_path(string $path = ''): string
{
    return join_paths(ROOT_PATH.'resource', $path);
}

/**
 * 运行时目录
 */
function runtime_path(string $path = ''): string
{
    return join_paths(ROOT_PATH.'runtime', $path);
}

if (! function_exists('com_create_guid')) {
    /**
     * 生成全局唯一标识符(GUID)
     *
     * @see Original: https://github.com/MicrosoftTranslator/Text-Translation-API-V3-PHP/blob/master/Translate.php#L26
     */
    function com_create_guid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xFFFF),
            mt_rand(0, 0xFFFF),
            mt_rand(0, 0xFFFF),
            mt_rand(0, 0x0FFF) | 0x4000,
            mt_rand(0, 0x3FFF) | 0x8000,
            mt_rand(0, 0xFFFF),
            mt_rand(0, 0xFFFF),
            mt_rand(0, 0xFFFF)
        );
    }
}

/**
 * 验证邮箱地址格式
 */
function is_email(string $email): bool
{
    return ! (filter_var($email, FILTER_VALIDATE_EMAIL) === false);
}

/**
 * 验证手机号码格式
 */
function is_mobile(string $mobile): bool
{
    $rule = '/^1[3-9]\d{9}$/';

    return 1 === preg_match($rule, $mobile);
}

/**
 * 页面跳转
 *
 * @param  string  $url 跳转地址
 * @param  int  $code 跳转代码
 */
function redirect(string $url, int $code = 302): void
{
    header('location:'.$url, true, $code);
    exit;
}

/**
 * JWT生成
 */
function jwt_encode(array $payload): string
{
    $config = Config::get('jwt');

    $payload = array_merge($config['payload'], ['body' => $payload]);

    return JWT::encode($payload, $config['key'], 'HS256');
}

/**
 * JWT解析
 */
function jwt_decode(string $jwt): array
{
    try {
        $config = Config::get('jwt');

        $decoded = JWT::decode($jwt, new Key($config['key'], 'HS256'));

        return (array) $decoded->body;
    } catch (Exception $e) {
        return [];
    }
}

/**
 * 字符串命名风格转换
 * type 0 将Java风格转换为C的风格 1 将C风格转换为Java的风格
 *
 * @param  string  $name 字符串
 * @param  int  $type 转换类型
 * @param  bool  $ucFirst 首字母是否大写（驼峰规则）
 */
function parse_name(string $name, int $type = 0, bool $ucFirst = true): string
{
    if ($type) {
        $name = preg_replace_callback('/_([a-zA-Z])/', function ($match) {
            return strtoupper($match[1]);
        }, $name);

        return $ucFirst ? ucfirst($name) : lcfirst($name);
    }

    return strtolower(trim(preg_replace('/[A-Z]/', '_\\0', $name), '_'));
}

/**
 * 缓存管理
 *
 * @param  string  $name 缓存名称
 * @param  mixed  $value 缓存值
 * @param  mixed  $options 缓存参数
 */
function cache(string $name = null, $value = '', $options = null)
{
    static $_cache;
    if (is_null($_cache)) {
        $_cache = new Cache();
    }

    if (is_null($name)) {
        return $_cache;
    }

    if ('' === $value) {
        // 获取缓存
        if (str_starts_with($name, '?')) {
            $name = substr($name, 1);

            return $_cache->has($name);
        }

        return $_cache->get($name);
    } elseif (is_null($value)) {
        // 删除缓存
        return $_cache->del($name);
    }

    // 缓存数据
    if (is_array($options)) {
        $expire = $options['expire'] ?? null; //修复查询缓存无法设置过期时间
    } else {
        $expire = $options;
    }

    return $_cache->set($name, $value, $expire);
}

/**
 * 获取设置配置
 *
 * @param  string  $key 配置项
 * @param  mixed  $value 配置值
 */
function config($key = null, $value = null)
{
    if (func_num_args() <= 1) {
        return Config::get($key);
    } else {
        return Config::set($key, $value);
    }
}

/**
 * URL生成
 *
 * @param  string|null  $route 地址
 * @param  array  $params 参数
 * @return string
 */
function url(string $route = null, array $params = [])
{
    return Route::url($route, $params);
}

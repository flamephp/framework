<?php

declare(strict_types=1);

namespace Flame\Http;

use Exception;

class Request
{
    /**
     * Retrieve a member of the $_GET super global
     *
     * If no $key is passed, returns the entire $_GET array.
     *
     * @param  string  $key
     * @param  mixed  $default Default value to use if key not found
     * @return mixed Returns null if key does not exist
     */
    public static function get($key = null, $default = null)
    {
        if (null === $key) {
            return $_GET;
        }

        return (isset($_GET[$key])) ? $_GET[$key] : $default;
    }

    /**
     * Retrieve a member of the $_POST super global
     *
     * If no $key is passed, returns the entire $_POST array.
     *
     * @param  string  $key
     * @param  mixed  $default Default value to use if key not found
     * @return mixed Returns null if key does not exist
     */
    public static function post($key = null, $default = null)
    {
        if (null === $key) {
            return $_POST;
        }

        return (isset($_POST[$key])) ? $_POST[$key] : $default;
    }

    /**
     * Retrieve a member of the $_COOKIE super global
     *
     * If no $key is passed, returns the entire $_COOKIE array.
     *
     * @param  string  $key
     * @param  mixed  $default Default value to use if key not found
     * @return mixed Returns null if key does not exist
     */
    public static function cookie($key = null, $default = null)
    {
        if (null === $key) {
            return $_COOKIE;
        }

        return (isset($_COOKIE[$key])) ? $_COOKIE[$key] : $default;
    }

    /**
     * Retrieve a member of the $_SERVER super global
     *
     * If no $key is passed, returns the entire $_SERVER array.
     *
     * @param  string  $key
     * @param  mixed  $default Default value to use if key not found
     * @return mixed Returns null if key does not exist
     */
    public static function server($key = null, $default = null)
    {
        if (null === $key) {
            return $_SERVER;
        }

        return (isset($_SERVER[$key])) ? $_SERVER[$key] : $default;
    }

    /**
     * Retrieve a member of the $_ENV super global
     *
     * If no $key is passed, returns the entire $_ENV array.
     *
     * @param  string  $key
     * @param  mixed  $default Default value to use if key not found
     * @return mixed Returns null if key does not exist
     */
    public static function env($key = null, $default = null)
    {
        if (null === $key) {
            return $_ENV;
        }

        return (isset($_ENV[$key])) ? $_ENV[$key] : $default;
    }

    /**
     * Get session
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public static function session($key = null, $default = null)
    {
        isset($_SESSION) || session_start();
        if (null === $key) {
            return $_SESSION;
        }

        return (isset($_SESSION[$key])) ? $_SESSION[$key] : $default;
    }

    /**
     * Return the value of the given HTTP header. Pass the header name as the
     * plain, HTTP-specified header name. Ex.: Ask for 'Accept' to get the
     * Accept header, 'Accept-Encoding' to get the Accept-Encoding header.
     *
     * @throws Exception
     */
    public static function header(string $header)
    {
        if (empty($header)) {
            return null;
        }

        if (function_exists('apache_request_headers') && $result = apache_request_headers()) {
            $headers = $result;
        } else {
            $headers = [];
            $server = $_SERVER;
            foreach ($server as $key => $val) {
                if (str_starts_with($key, 'HTTP_')) {
                    $key = str_replace('_', '-', strtolower(substr($key, 5)));
                    $headers[$key] = $val;
                }
            }
            if (isset($server['CONTENT_TYPE'])) {
                $headers['content-type'] = $server['CONTENT_TYPE'];
            }
            if (isset($server['CONTENT_LENGTH'])) {
                $headers['content-length'] = $server['CONTENT_LENGTH'];
            }
        }

        $headers = array_change_key_case($headers);

        if (! empty($headers[strtolower($header)])) {
            return $headers[strtolower($header)];
        }

        return null;
    }

    /**
     * Return current url
     */
    public static function currentUrl(): string
    {
        $url = 'http';

        if ('on' == self::server('HTTPS')) {
            $url .= 's';
        }

        $url .= '://'.self::server('HTTP_HOST');

        $port = self::server('SERVER_PORT');
        if (80 != $port) {
            $url .= ":{$port}";
        }

        return $url.self::server('REQUEST_URI');
    }

    /**
     * Was the request made by POST?
     */
    public static function isPost(): bool
    {
        if ('POST' == self::server('REQUEST_METHOD')) {
            return true;
        }

        return false;
    }

    /**
     * Was the request made by GET?
     */
    public static function isGet(): bool
    {
        if ('GET' == self::server('REQUEST_METHOD')) {
            return true;
        }

        return false;
    }

    /**
     * Was the request made by PUT?
     */
    public static function isPut(): bool
    {
        if ('PUT' == self::server('REQUEST_METHOD')) {
            return true;
        }

        return false;
    }

    /**
     * Was the request made by DELETE?
     */
    public static function isDelete(): bool
    {
        if ('DELETE' == self::server('REQUEST_METHOD')) {
            return true;
        }

        return false;
    }

    /**
     * Was the request made by HEAD?
     */
    public static function isHead(): bool
    {
        if ('HEAD' == self::server('REQUEST_METHOD')) {
            return true;
        }

        return false;
    }

    /**
     * Was the request made by OPTIONS?
     */
    public static function isOptions(): bool
    {
        if ('OPTIONS' == self::server('REQUEST_METHOD')) {
            return true;
        }

        return false;
    }

    /**
     * Is the request a Javascript XMLHttpRequest?
     *
     * Should work with Prototype/Script.aculo.us, possibly others.
     *
     * @throws Exception
     */
    public static function isAjax(): bool
    {
        return 'XMLHttpRequest' == self::header('X_REQUESTED_WITH');
    }

    /**
     * Is this a Flash request?
     *
     * @throws Exception
     */
    public static function isFlashRequest(): bool
    {
        return 'Shockwave Flash' == self::header('USER_AGENT');
    }

    /**
     * Is https secure request
     */
    public static function isSecure(): bool
    {
        return 'https' === self::scheme();
    }

    /**
     * Check if search engine spider
     */
    public static function isSpider($ua = null): bool
    {
        is_null($ua) && $ua = $_SERVER['HTTP_USER_AGENT'];
        $ua = strtolower($ua);
        $spiders = ['bot', 'crawl', 'spider', 'slurp', 'sohu-search', 'lycos', 'robozilla'];
        foreach ($spiders as $spider) {
            if (str_contains($ua, $spider)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the request URI scheme
     */
    public static function scheme(): string
    {
        return ('on' == self::server('HTTPS')) ? 'https' : 'http';
    }

    /**
     * Get Client Ip
     */
    public static function clientIp(string $default = '0.0.0.0'): string
    {
        $keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];

        foreach ($keys as $key) {
            if (empty($_SERVER[$key])) {
                continue;
            }
            $ips = explode(',', $_SERVER[$key], 1);
            $ip = $ips[0];
            $l = ip2long($ip);
            if ((false !== $l) && ($ip === long2ip($l))) {
                return $ip;
            }
        }

        return $default;
    }
}

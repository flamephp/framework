<?php

declare(strict_types=1);

namespace Flame\Queue;

use Flame\Support\Str;

class Manager
{
    private array $config;

    private static array $connections = [];

    public function __construct()
    {
        $this->config = config('queue.connections.'.config('queue.default'));
    }

    public function instance(string $queueName = 'default')
    {
        $driverType = Str::studly(config('queue.default')).'Queue';
        if (! isset(self::$connections[$driverType])) {
            $driverType = __NAMESPACE__.'\\'.$driverType;
            self::$connections[$driverType] = new $driverType($this->config, $queueName);
        }

        return self::$connections[$driverType];
    }
}

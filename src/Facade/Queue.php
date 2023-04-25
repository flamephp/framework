<?php

declare(strict_types=1);

namespace Flame\Facade;

use Flame\Queue\Manager;
use Flame\Support\Facade;

/**
 * @method static instance(string $queueName)
 */
class Queue extends Facade
{
    protected static function getFacadeClass(): string
    {
        return Manager::class;
    }
}

<?php

declare(strict_types=1);

namespace Flame\Queue;

interface JobInterface
{
    public function handle();
}

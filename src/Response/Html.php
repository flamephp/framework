<?php

declare(strict_types=1);

namespace Flame\Response;

use Flame\Cookie\Cookie;
use Flame\Http\Response;

class Html extends Response
{
    protected string $contentType = 'text/html';

    public function __construct($data = '', int $code = 200)
    {
        $this->init($data, $code);
        $this->cookie = new Cookie();
    }
}

<?php

declare(strict_types=1);

namespace Flame\Auth;

use Flame\Auth\Exception\ExtractTokenException;
use Flame\Http\Request;
use Exception;

class BearerTokenExtractor implements TokenExtractorInterface
{
    /**
     * 提取token
     * @throws Exception
     */
    public function extractToken(): string
    {
        $authorization = Request::header('Authorization');

        if (!str_starts_with($authorization, 'Bearer ')) {
            throw new ExtractTokenException('Failed to extract token.');
        }

        return substr($authorization, 7);
    }
}

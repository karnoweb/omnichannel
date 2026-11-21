<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Exceptions;

use Karnoweb\Omnichannel\Http\RateLimitContext;
use RuntimeException;

final class RateLimitExceededException extends RuntimeException
{
    public function __construct(
        public readonly RateLimitContext $context,
        public readonly int $retryAfterSeconds,
        string $message = 'Local API request quota exceeded.',
    ) {
        parent::__construct($message);
    }
}
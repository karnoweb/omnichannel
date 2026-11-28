<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Tests\Feature;

use Karnoweb\Omnichannel\Exceptions\RateLimitExceededException;
use Karnoweb\Omnichannel\Http\RateLimitContext;
use Karnoweb\Omnichannel\Services\ApiRequestRateLimiter;
use Karnoweb\Omnichannel\Tests\TestCase;

final class ConcurrentRateLimitTest extends TestCase
{
    public function test_second_request_exceeds_quota_under_same_fingerprint(): void
    {
        config(['omnichannel.channels.demo.rate_limit' => ['attempts' => 1, 'per_seconds' => 60]]);
        $limiter = app(ApiRequestRateLimiter::class);
        $context = new RateLimitContext('demo', hash('sha256', 'demo|shared'));

        $limiter->consume($context);
        $this->expectException(RateLimitExceededException::class);
        $limiter->consume($context);
    }
}

<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Tests\Feature;

use Karnoweb\Omnichannel\Exceptions\RateLimitExceededException;
use Karnoweb\Omnichannel\Http\RateLimitContext;
use Karnoweb\Omnichannel\Services\ApiRequestRateLimiter;
use Karnoweb\Omnichannel\Tests\TestCase;

final class SharedAccountRateLimitTest extends TestCase
{
    public function test_two_connections_with_same_external_account_share_quota(): void
    {
        config(['omnichannel.channels.telegram.rate_limit' => ['attempts' => 1, 'per_seconds' => 60]]);
        $limiter = app(ApiRequestRateLimiter::class);
        $fingerprint = RateLimitContext::forConnection('telegram', 'acc-shared', 1)->accountFingerprint;
        $contextA = new RateLimitContext('telegram', $fingerprint);
        $contextB = new RateLimitContext('telegram', $fingerprint);

        $limiter->consume($contextA);
        $this->expectException(RateLimitExceededException::class);
        $limiter->consume($contextB);
    }
}
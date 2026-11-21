<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Tests\Integration;

use Karnoweb\Omnichannel\Exceptions\RateLimitExceededException;
use Karnoweb\Omnichannel\Http\RateLimitContext;
use Karnoweb\Omnichannel\Services\ApiRequestRateLimiter;
use Karnoweb\Omnichannel\Tests\TestCase;

/**
 * @group redis
 */
final class RedisRateLimitIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (! extension_loaded('redis') && config('cache.default') !== 'redis') {
            $this->markTestSkipped('Redis is not configured for integration tests.');
        }
    }

    public function test_shared_limiter_enforces_quota_with_redis_store(): void
    {
        config([
            'cache.default' => 'redis',
            'omnichannel.api_rate_limit.cache_store' => 'redis',
            'omnichannel.channels.demo.rate_limit' => ['attempts' => 1, 'per_seconds' => 60],
        ]);
        $limiter = app(ApiRequestRateLimiter::class);
        $context = RateLimitContext::forConnection('demo', 'redis-acct', 99);
        $limiter->consume($context);
        $this->expectException(RateLimitExceededException::class);
        $limiter->consume($context);
    }
}
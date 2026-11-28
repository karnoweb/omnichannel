<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Tests\Integration;

use Illuminate\Support\Facades\Cache;
use Karnoweb\Omnichannel\Exceptions\RateLimitExceededException;
use Karnoweb\Omnichannel\Http\RateLimitContext;
use Karnoweb\Omnichannel\Services\ApiRequestRateLimiter;
use Karnoweb\Omnichannel\Tests\TestCase;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Process\Process;

#[Group('redis')]
final class RedisRateLimitIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $host = getenv('OMNICHANNEL_TEST_REDIS_HOST');
        if ($host === false || $host === '') {
            $this->markTestSkipped('Set OMNICHANNEL_TEST_REDIS_HOST to an isolated Redis service.');
        }

        config([
            'database.redis.client' => 'phpredis',
            'database.redis.cache' => [
                'host' => $host,
                'port' => (int) (getenv('OMNICHANNEL_TEST_REDIS_PORT') ?: 6379),
                'database' => 0,
                'timeout' => 1,
            ],
            'database.redis.options.prefix' => '',
            'cache.prefix' => 'omnichannel-test:'.(getenv('OMNICHANNEL_TEST_REDIS_RUN') ?: bin2hex(random_bytes(12))).':',
            'cache.stores.redis.lock_connection' => 'cache',
            'omnichannel.api_rate_limit.cache_store' => 'redis',
            'omnichannel.channels.demo.rate_limit' => ['attempts' => 1, 'per_seconds' => 30],
        ]);
    }

    public function test_shared_limiter_enforces_quota_with_redis_store(): void
    {
        $limiter = app(ApiRequestRateLimiter::class);
        $context = RateLimitContext::forConnection('demo', 'redis-acct', 99);
        $expected = getenv('OMNICHANNEL_TEST_REDIS_EXPECTED');
        if ($expected === 'blocked') {
            $this->expectException(RateLimitExceededException::class);
        }

        $limiter->consume($context);
        if ($expected !== false) {
            self::assertTrue(Cache::store('redis')->has($context->cacheKey()));

            return;
        }

        $limiter->consume(RateLimitContext::forConnection('demo', 'other-account', 99));
        self::assertTrue(Cache::store('redis')->lock($context->cacheKey().':lock', 1)->get(fn () => true));
        $this->expectException(RateLimitExceededException::class);
        $limiter->consume($context);
    }

    public function test_concurrent_processes_share_one_quota(): void
    {
        $worker = function (string $expected, string $run): Process {
            return new Process(
                [PHP_BINARY, 'vendor/bin/phpunit', '--filter', 'test_shared_limiter_enforces_quota_with_redis_store'],
                dirname(__DIR__, 2),
                ['OMNICHANNEL_TEST_REDIS_RUN' => $run, 'OMNICHANNEL_TEST_REDIS_EXPECTED' => $expected],
            );
        };

        $run = bin2hex(random_bytes(12));
        $first = $worker('allowed', $run);
        $second = $worker('allowed', $run);
        $first->start();
        $second->start();
        $exitCodes = [$first->wait(), $second->wait()];
        sort($exitCodes);
        self::assertSame([0, 2], $exitCodes, $first->getOutput().$second->getOutput());

        $blocked = $worker('blocked', $run);
        self::assertSame(0, $blocked->run(), $blocked->getOutput().$blocked->getErrorOutput());
        self::assertStringContainsString('OK (1 test', $blocked->getOutput());
    }

    public function test_abandoned_lock_expires(): void
    {
        $store = Cache::store('redis');
        self::assertTrue($store->lock('abandoned', 1)->get());
        self::assertFalse($store->lock('abandoned', 1)->get());
        self::assertTrue($store->lock('abandoned', 1)->block(4, fn () => true));
        self::assertTrue($store->lock('abandoned', 1)->get(fn () => true));
    }

    public function test_unavailable_redis_does_not_grant_quota(): void
    {
        config(['database.redis.cache.host' => 'invalid redis host']);
        $this->expectException(\RedisException::class);
        app(ApiRequestRateLimiter::class)->consume(RateLimitContext::forConnection('demo', 'unavailable', 99));
    }
}

<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Tests\Feature;

use Illuminate\Support\Facades\Http;
use Karnoweb\Omnichannel\Channels\Instagram\InstagramApiClient;
use Karnoweb\Omnichannel\Exceptions\RateLimitExceededException;
use Karnoweb\Omnichannel\Http\RateLimitContext;
use Karnoweb\Omnichannel\Services\ApiRequestRateLimiter;
use Karnoweb\Omnichannel\Services\ApiRequestRateLimitScope;
use Karnoweb\Omnichannel\Tests\TestCase;

final class SharedAccountRateLimitTest extends TestCase
{
    public function test_instagram_connections_share_account_quota_until_the_window_expires(): void
    {
        config([
            'cache.default' => 'array',
            'omnichannel.api_rate_limit.cache_store' => 'array',
            'omnichannel.channels.instagram.graph_url' => 'https://instagram.test',
            'omnichannel.channels.instagram.rate_limit' => ['attempts' => 1, 'per_seconds' => 60],
        ]);
        Http::preventStrayRequests();
        Http::fake(['https://instagram.test/*' => Http::response(['user_id' => 'sample-account'])]);
        $scope = app(ApiRequestRateLimitScope::class);
        $client = app(InstagramApiClient::class);
        $request = fn (string $account, int $connection) => $scope->usingDriverKey(
            'instagram',
            $account,
            $connection,
            fn () => $client->me('sample-token'),
        );

        self::assertFalse($request('sample-account', 1)->failed());
        self::assertTrue($request('sample-account', 2)->isLocalRateLimited());
        self::assertFalse($request('other-account', 3)->failed());
        Http::assertSentCount(2);

        $this->travel(61)->seconds(function () use ($request): void {
            self::assertFalse($request('sample-account', 2)->failed());
        });
        Http::assertSentCount(3);
    }

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

<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Tests\Feature;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Karnoweb\Omnichannel\Http\ApiResponse;
use Karnoweb\Omnichannel\Http\RateLimitContext;
use Karnoweb\Omnichannel\Services\ApiRequestRateLimitScope;
use Karnoweb\Omnichannel\Tests\Support\DemoTestApiClient;
use Karnoweb\Omnichannel\Tests\TestCase;

final class DriverRequestCountingTest extends TestCase
{
    public function test_retry_safe_http_performs_multiple_consumes(): void
    {
        config([
            'omnichannel.channels.demo.rate_limit' => ['attempts' => 5, 'per_seconds' => 60],
            'omnichannel.http.retries' => 2,
        ]);
        Http::fake(function () {
            throw new ConnectionException('down');
        });

        $scope = app(ApiRequestRateLimitScope::class);
        $client = new class extends DemoTestApiClient
        {
            public function retryPing(): ApiResponse
            {
                return $this->get('https://demo.local/ping', [], null, true);
            }
        };

        $scope->usingDriverKey('demo', 'acct-1', 1, fn () => $client->retryPing());

        $fingerprint = RateLimitContext::forConnection('demo', 'acct-1', 1)->cacheKey();
        self::assertGreaterThanOrEqual(2, (int) cache()->get($fingerprint, 0));
    }
}

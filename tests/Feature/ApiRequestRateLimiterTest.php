<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Tests\Feature;

use Illuminate\Support\Facades\Http;
use Karnoweb\Omnichannel\Http\ApiResponse;
use Karnoweb\Omnichannel\Services\ApiRequestRateLimitScope;
use Karnoweb\Omnichannel\Tests\Support\DemoTestApiClient;
use Karnoweb\Omnichannel\Tests\TestCase;

final class ApiRequestRateLimiterTest extends TestCase
{
    public function test_local_rate_limit_returns_distinct_response(): void
    {
        config(['omnichannel.channels.demo.rate_limit' => ['attempts' => 1, 'per_seconds' => 60]]);
        Http::fake(['https://demo.local/*' => Http::response(['ok' => true])]);

        $scope = app(ApiRequestRateLimitScope::class);
        $client = new DemoTestApiClient;

        $scope->usingDriverKey('demo', 'acct-1', 1, fn () => $client->ping());
        $response = $scope->usingDriverKey('demo', 'acct-1', 1, fn () => $client->ping());

        self::assertInstanceOf(ApiResponse::class, $response);
        self::assertTrue($response->isLocalRateLimited());
    }
}

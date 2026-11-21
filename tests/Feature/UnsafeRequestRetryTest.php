<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Tests\Feature;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Karnoweb\Omnichannel\Http\ApiResponse;
use Karnoweb\Omnichannel\Http\BaseApiClient;
use Karnoweb\Omnichannel\Tests\TestCase;

final class UnsafeRequestRetryTest extends TestCase
{
    public function test_mutating_post_is_not_retried_after_transport_failure(): void
    {
        $attempts = 0;
        Http::fake(function (Request $request) use (&$attempts) {
            $attempts++;
            throw new ConnectionException('connection lost after request write');
        });

        $response = (new TestApiClient)->postRequest('https://api.example/publish');

        self::assertTrue($response->isTransportFailure());
        self::assertSame(1, $attempts);
    }

    public function test_explicitly_safe_read_can_use_the_configured_retry_policy(): void
    {
        $attempts = 0;
        Http::fake(function (Request $request) use (&$attempts) {
            $attempts++;
            if ($attempts === 1) {
                throw new ConnectionException('temporary read failure');
            }

            return Http::response(['ok' => true], 200);
        });

        $response = (new TestApiClient)->getRequest('https://api.example/status', retrySafe: true);

        self::assertFalse($response->failed());
        self::assertSame(2, $attempts);
    }
}

final class TestApiClient extends BaseApiClient
{
    public function postRequest(string $url): ApiResponse
    {
        return $this->post($url, ['message' => 'hello']);
    }

    public function getRequest(string $url, bool $retrySafe = false): ApiResponse
    {
        return $this->get($url, retrySafe: $retrySafe);
    }
}

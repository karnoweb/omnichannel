<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Tests\Feature;

use GuzzleHttp\Psr7\Response as PsrResponse;
use Illuminate\Http\Client\Response;
use Karnoweb\Omnichannel\Http\ApiResponse;
use Karnoweb\Omnichannel\Tests\TestCase;

final class RetryAfterPropagationTest extends TestCase
{
    public function test_local_rate_limit_carries_retry_after_metadata(): void
    {
        $response = ApiResponse::localRateLimited(42);
        self::assertSame(42, $response->retryAfterSeconds());
        self::assertTrue($response->isLocalRateLimited());
    }

    public function test_platform_429_retry_after_is_parsed(): void
    {
        $response = ApiResponse::fromResponse(new Response(new PsrResponse(429, ['Retry-After' => ['15']])));
        self::assertSame(15, $response->retryAfterSeconds());
        self::assertTrue($response->isPlatformRateLimited());
    }
}
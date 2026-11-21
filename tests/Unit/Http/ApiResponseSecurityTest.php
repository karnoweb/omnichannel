<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Tests\Unit\Http;

use Illuminate\Support\Facades\Http;
use Karnoweb\Omnichannel\Http\ApiResponse;
use Karnoweb\Omnichannel\Tests\TestCase;

final class ApiResponseSecurityTest extends TestCase
{
    public function test_raw_response_and_transport_errors_are_redacted_before_logging(): void
    {
        $token = 'audit-placeholder-token-value-abcdef';
        Http::fake(['https://example.test/*' => Http::response('token='.$token, 502)]);
        $response = Http::get('https://example.test/?access_token='.$token);
        $apiResponse = ApiResponse::fromResponse($response);

        self::assertStringNotContainsString($token, json_encode($apiResponse->toLogArray()) ?: '');
    }
}

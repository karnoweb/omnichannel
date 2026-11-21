<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Channels\Gap;

use Karnoweb\Omnichannel\Enums\FailureClass;
use Karnoweb\Omnichannel\Http\ApiResponse;

final class GapErrorMapper
{
    public function classify(ApiResponse $response): FailureClass
    {
        if ($response->isTransportFailure()) {
            return FailureClass::Transient;
        }

        $error = mb_strtolower((string) ($response->string('error') ?? ''));

        if ($error !== '') {
            return match (true) {
                str_contains($error, 'token') => FailureClass::Authentication,
                str_contains($error, 'permission'), str_contains($error, 'access') => FailureClass::Permission,
                str_contains($error, 'chat'), str_contains($error, 'invalid') => FailureClass::Validation,
                default => FailureClass::fromHttpStatus($response->status ?? 400),
            };
        }

        return FailureClass::fromHttpStatus($response->status ?? 0);
    }

    public function errorCodeString(ApiResponse $response): string
    {
        if ($response->isTransportFailure()) {
            return 'gap.transport';
        }

        return 'gap.http.' . ($response->status ?? 0);
    }

    public function technicalMessage(ApiResponse $response): string
    {
        if ($response->transportError !== null) {
            return $response->transportError;
        }

        return $response->string('error')
            ?? $response->string('message')
            ?? $response->rawBody
            ?? 'Gap returned HTTP ' . ($response->status ?? 0) . ' with no description.';
    }
}

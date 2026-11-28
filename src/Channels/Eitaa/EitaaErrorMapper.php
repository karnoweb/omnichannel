<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Channels\Eitaa;

use Karnoweb\Omnichannel\Enums\FailureClass;
use Karnoweb\Omnichannel\Http\ApiResponse;

final class EitaaErrorMapper
{
    public function classify(ApiResponse $response): FailureClass
    {
        if ($response->isTransportFailure()) {
            return FailureClass::Transient;
        }

        $description = mb_strtolower((string) ($response->string('description') ?? ''));

        if (str_contains($description, 'token') || str_contains($description, 'unauthorized')) {
            return FailureClass::Authentication;
        }

        if (str_contains($description, 'chat') || str_contains($description, 'not found')) {
            return FailureClass::Permission;
        }

        if (str_contains($description, 'too long') || str_contains($description, 'invalid')) {
            return FailureClass::Validation;
        }

        $code = $response->int('error_code') ?? $response->status ?? 0;

        return $code === 0 ? FailureClass::Transient : FailureClass::fromHttpStatus($code);
    }

    public function errorCodeString(ApiResponse $response): string
    {
        if ($response->isTransportFailure()) {
            return 'eitaa.transport';
        }

        $code = $response->int('error_code') ?? $response->status ?? 0;

        return "eitaa.http.{$code}";
    }

    public function technicalMessage(ApiResponse $response): string
    {
        if ($response->transportError !== null) {
            return $response->transportError;
        }

        return $response->string('description')
            ?? $response->rawBody
            ?? 'The Eitaa gateway returned HTTP '.($response->status ?? 0).' with no description.';
    }
}

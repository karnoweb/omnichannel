<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Channels\Rubika;

use Karnoweb\Omnichannel\Enums\FailureClass;
use Karnoweb\Omnichannel\Http\ApiResponse;

final class RubikaErrorMapper
{
    /** @var array<string, FailureClass> */
    private const STATUS_MAP = [
        'INVALID_ACCESS' => FailureClass::Authentication,
        'INVALID_INPUT' => FailureClass::Validation,
        'NOT_REGISTERED' => FailureClass::Authentication,
        'TOO_REQUESTS' => FailureClass::RateLimited,
        'INTERNAL_ERROR' => FailureClass::Transient,
        'PERMISSION_DENIED' => FailureClass::Permission,
    ];

    public function classify(ApiResponse $response): FailureClass
    {
        if ($response->isTransportFailure()) {
            return FailureClass::Transient;
        }

        $status = mb_strtoupper((string) ($response->string('status') ?? ''));

        if ($status !== '' && isset(self::STATUS_MAP[$status])) {
            return self::STATUS_MAP[$status];
        }

        return FailureClass::fromHttpStatus($response->status ?? 0);
    }

    public function errorCodeString(ApiResponse $response): string
    {
        if ($response->isTransportFailure()) {
            return 'rubika.transport';
        }

        $status = $response->string('status');

        return $status !== null && mb_strtoupper($status) !== 'OK'
            ? 'rubika.' . mb_strtolower($status)
            : 'rubika.http.' . ($response->status ?? 0);
    }

    public function technicalMessage(ApiResponse $response): string
    {
        if ($response->transportError !== null) {
            return $response->transportError;
        }

        return $response->string('message')
            ?? $response->string('status')
            ?? $response->rawBody
            ?? 'Rubika returned HTTP ' . ($response->status ?? 0) . ' with no description.';
    }
}

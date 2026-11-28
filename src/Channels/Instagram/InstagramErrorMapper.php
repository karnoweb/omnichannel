<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Channels\Instagram;

use Karnoweb\Omnichannel\Enums\FailureClass;
use Karnoweb\Omnichannel\Http\ApiResponse;

final class InstagramErrorMapper
{
    /** @var list<int> */
    private const AUTH_CODES = [102, 190, 463, 467];

    /** @var list<int> */
    private const PERMISSION_CODES = [10, 200, 803];

    /** @var list<int> */
    private const RATE_LIMIT_CODES = [4, 17, 32, 613, 80007, 80014];

    /** @var list<int> */
    private const TRANSIENT_CODES = [1, 2, 341];

    public function classify(ApiResponse $response): FailureClass
    {
        if ($response->isTransportFailure()) {
            return FailureClass::Transient;
        }

        $code = $this->errorCode($response);

        if ($code !== null) {
            $byCode = match (true) {
                in_array($code, self::AUTH_CODES, true) => FailureClass::Authentication,
                in_array($code, self::PERMISSION_CODES, true) => FailureClass::Permission,
                in_array($code, self::RATE_LIMIT_CODES, true) => FailureClass::RateLimited,
                in_array($code, self::TRANSIENT_CODES, true) => FailureClass::Transient,
                default => null,
            };

            if ($byCode !== null) {
                return $byCode;
            }
        }

        if ($this->errorType($response) === 'OAuthException') {
            return FailureClass::Authentication;
        }

        return FailureClass::fromHttpStatus($response->status ?? 0);
    }

    public function errorCodeString(ApiResponse $response): string
    {
        if ($response->isTransportFailure()) {
            return 'instagram.transport';
        }

        $code = $this->errorCode($response);
        $subcode = $response->int('error.error_subcode');

        if ($code === null) {
            return 'instagram.http.'.($response->status ?? 0);
        }

        return $subcode !== null
            ? "instagram.graph.{$code}.{$subcode}"
            : "instagram.graph.{$code}";
    }

    public function technicalMessage(ApiResponse $response): string
    {
        if ($response->transportError !== null) {
            return $response->transportError;
        }

        return $response->string('error.error_user_msg')
            ?? $response->string('error.message')
            ?? $response->rawBody
            ?? 'Instagram returned HTTP '.($response->status ?? 0).' with no error body.';
    }

    private function errorCode(ApiResponse $response): ?int
    {
        return $response->int('error.code');
    }

    private function errorType(ApiResponse $response): ?string
    {
        return $response->string('error.type');
    }
}

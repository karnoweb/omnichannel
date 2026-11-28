<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Channels\Basalam;

use Karnoweb\Omnichannel\Enums\FailureClass;
use Karnoweb\Omnichannel\Http\ApiResponse;

final class BasalamErrorMapper
{
    public function classify(ApiResponse $response): FailureClass
    {
        if ($response->isTransportFailure()) {
            return FailureClass::Transient;
        }

        if ($response->status === 422) {
            return FailureClass::Validation;
        }

        return FailureClass::fromHttpStatus($response->status ?? 0);
    }

    public function errorCodeString(ApiResponse $response): string
    {
        if ($response->isTransportFailure()) {
            return 'basalam.transport';
        }

        $code = $response->string('code') ?? $response->string('error_code');

        return $code !== null
            ? "basalam.{$code}"
            : 'basalam.http.'.($response->status ?? 0);
    }

    public function technicalMessage(ApiResponse $response): string
    {
        if ($response->transportError !== null) {
            return $response->transportError;
        }

        $detail = $response->get('detail');

        if (is_array($detail)) {
            $messages = [];

            foreach ($detail as $item) {
                if (! is_array($item)) {
                    continue;
                }

                $field = is_array($item['loc'] ?? null)
                    ? implode('.', array_map('strval', $item['loc']))
                    : null;

                $message = is_string($item['msg'] ?? null) ? $item['msg'] : null;

                if ($message !== null) {
                    $messages[] = $field !== null ? "{$field}: {$message}" : $message;
                }
            }

            if ($messages !== []) {
                return implode('; ', $messages);
            }
        }

        if (is_string($detail)) {
            return $detail;
        }

        return $response->string('message')
            ?? $response->string('error')
            ?? $response->rawBody
            ?? 'Basalam returned HTTP '.($response->status ?? 0).' with no error body.';
    }
}

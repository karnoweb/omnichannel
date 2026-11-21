<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Channels\Messaging;

use Karnoweb\Omnichannel\Enums\FailureClass;
use Karnoweb\Omnichannel\Http\ApiResponse;

class BotApiErrorMapper
{
    /** @var list<string> */
    private const PERMISSION_PHRASES = [
        'chat not found',
        'bot was kicked',
        'bot is not a member',
        'not enough rights',
        'have no rights',
        'chat_write_forbidden',
        'user is deactivated',
        'bot was blocked',
    ];

    /** @var list<string> */
    private const AUTH_PHRASES = [
        'unauthorized',
        'token is invalid',
        'bot token',
    ];

    /** @var list<string> */
    private const VALIDATION_PHRASES = [
        'message is too long',
        'wrong file identifier',
        'wrong type of the web page content',
        'failed to get http url content',
        'photo_invalid_dimensions',
        'image_process_failed',
        'file is too big',
        'wrong remote file',
        'can\'t parse entities',
        'message to edit not found',
        'message to delete not found',
        'message can\'t be deleted',
    ];

    public function __construct(private readonly string $codePrefix) {}

    public function classify(ApiResponse $response): FailureClass
    {
        if ($response->isLocalRateLimited()) {
            return FailureClass::RateLimited;
        }

        if ($response->isTransportFailure()) {
            return FailureClass::Transient;
        }

        $description = mb_strtolower($this->description($response));

        foreach (self::AUTH_PHRASES as $phrase) {
            if (str_contains($description, $phrase)) {
                return FailureClass::Authentication;
            }
        }

        foreach (self::PERMISSION_PHRASES as $phrase) {
            if (str_contains($description, $phrase)) {
                return FailureClass::Permission;
            }
        }

        foreach (self::VALIDATION_PHRASES as $phrase) {
            if (str_contains($description, $phrase)) {
                return FailureClass::Validation;
            }
        }

        $errorCode = $response->int('error_code') ?? $response->status ?? 0;

        return FailureClass::fromHttpStatus($errorCode);
    }

    public function errorCodeString(ApiResponse $response): string
    {
        if ($response->isTransportFailure()) {
            return "{$this->codePrefix}.transport";
        }

        $code = $response->int('error_code') ?? $response->status ?? 0;

        return "{$this->codePrefix}.http.{$code}";
    }

    public function technicalMessage(ApiResponse $response): string
    {
        if ($response->transportError !== null) {
            return $response->transportError;
        }

        $description = $this->description($response);

        return $description !== ''
            ? $description
            : 'The channel returned HTTP ' . ($response->status ?? 0) . ' with no description.';
    }

    public function retryAfterSeconds(ApiResponse $response): ?int
    {
        return $response->retryAfterSeconds();
    }

    private function description(ApiResponse $response): string
    {
        return $response->string('description')
            ?? $response->string('message')
            ?? $response->rawBody
            ?? '';
    }
}

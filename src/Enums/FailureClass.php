<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Enums;

enum FailureClass: string
{
    case Transient = 'transient';
    case RateLimited = 'rate_limited';
    case Authentication = 'authentication';
    case Permission = 'permission';
    case Validation = 'validation';
    case Unsupported = 'unsupported';
    case Unknown = 'unknown';
    case UnknownOutcome = 'unknown_outcome';

    public function isRetryable(): bool
    {
        return match ($this) {
            self::Transient, self::RateLimited => true,
            self::Authentication, self::Permission, self::Validation, self::Unsupported, self::Unknown, self::UnknownOutcome => false,
        };
    }

    public function userMessageKey(): string
    {
        return 'omnichannel::omnichannel.failure.'.$this->value;
    }

    public function label(): string
    {
        return trans($this->userMessageKey());
    }

    public static function fromHttpStatus(int $status): self
    {
        return match (true) {
            $status === 401 => self::Authentication,
            $status === 403 => self::Permission,
            $status === 404 => self::Validation,
            $status === 408 => self::Transient,
            $status === 422 => self::Validation,
            $status === 429 => self::RateLimited,
            $status >= 500 => self::Transient,
            $status >= 400 => self::Validation,
            default => self::Unknown,
        };
    }
}

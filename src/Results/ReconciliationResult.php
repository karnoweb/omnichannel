<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Results;

final readonly class ReconciliationResult
{
    public const ConfirmedSuccess = 'confirmed_success';
    public const ConfirmedFailure = 'confirmed_failure';
    public const Pending = 'pending';
    public const PartialSuccess = 'partial_success';
    public const Unknown = 'unknown';
    public const Unsupported = 'unsupported';

    public function __construct(
        public string $outcome,
        public ?string $message = null,
        /** @var array<string, mixed> */
        public array $metadata = [],
    ) {}

    public function succeeded(): bool
    {
        return $this->outcome === self::ConfirmedSuccess;
    }
}
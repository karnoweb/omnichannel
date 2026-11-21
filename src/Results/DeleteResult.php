<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Results;

use Karnoweb\Omnichannel\Enums\FailureClass;
use Karnoweb\Omnichannel\Services\SecretRedactor;

final readonly class DeleteResult
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    private function __construct(
        public bool $successful,
        public ?FailureClass $failureClass = null,
        public ?string $errorCode = null,
        public ?string $errorMessage = null,
        public array $metadata = [],
    ) {}

    /**
     * @param  array<string, mixed>  $metadata
     */
    public static function success(array $metadata = []): self
    {
        return new self(successful: true, metadata: app(SecretRedactor::class)->redactAndTrim($metadata));
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public static function failure(
        FailureClass $failureClass,
        string $errorMessage,
        ?string $errorCode = null,
        array $metadata = [],
    ): self {
        return new self(
            successful: false,
            failureClass: $failureClass,
            errorCode: $errorCode !== null ? app(SecretRedactor::class)->redactString($errorCode) : null,
            errorMessage: app(SecretRedactor::class)->redactString($errorMessage, $metadata),
            metadata: app(SecretRedactor::class)->redactAndTrim($metadata),
        );
    }

    public static function unsupported(string $reason): self
    {
        return new self(
            successful: false,
            failureClass: FailureClass::Unsupported,
            errorCode: 'capability.delete.unsupported',
            errorMessage: app(SecretRedactor::class)->redactString($reason),
        );
    }

    public function failed(): bool
    {
        return ! $this->successful;
    }

    public function isRetryable(): bool
    {
        return $this->failed() && ($this->failureClass?->isRetryable() ?? false);
    }

    public function userMessage(): ?string
    {
        return $this->failureClass?->label();
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'successful' => $this->successful,
            'failure_class' => $this->failureClass?->value,
            'error_code' => $this->errorCode !== null ? app(SecretRedactor::class)->redactString($this->errorCode) : null,
            'error_message' => $this->errorMessage !== null ? app(SecretRedactor::class)->redactString($this->errorMessage) : null,
            'retryable' => $this->isRetryable(),
            'metadata' => app(SecretRedactor::class)->redactAndTrim($this->metadata),
        ];
    }
}

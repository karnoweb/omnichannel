<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Results;

use DateTimeImmutable;
use Karnoweb\Omnichannel\Enums\FailureClass;
use Karnoweb\Omnichannel\Services\SecretRedactor;

final readonly class ConnectionTestResult
{
    /**
     * @param  array<string, mixed>  $account
     */
    private function __construct(
        public bool $successful,
        public ?string $accountId = null,
        public ?string $accountName = null,
        public ?DateTimeImmutable $expiresAt = null,
        public array $account = [],
        public ?FailureClass $failureClass = null,
        public ?string $errorCode = null,
        public ?string $errorMessage = null,
    ) {}

    /**
     * @param  array<string, mixed>  $account
     */
    public static function success(
        ?string $accountId = null,
        ?string $accountName = null,
        ?DateTimeImmutable $expiresAt = null,
        array $account = [],
    ): self {
        return new self(
            successful: true,
            accountId: $accountId !== null ? app(SecretRedactor::class)->redactString($accountId) : null,
            accountName: $accountName !== null ? app(SecretRedactor::class)->redactString($accountName) : null,
            expiresAt: $expiresAt,
            account: app(SecretRedactor::class)->redactAndTrim($account),
        );
    }

    public static function failure(
        FailureClass $failureClass,
        string $errorMessage,
        ?string $errorCode = null,
    ): self {
        return new self(
            successful: false,
            failureClass: $failureClass,
            errorCode: $errorCode !== null ? app(SecretRedactor::class)->redactString($errorCode) : null,
            errorMessage: app(SecretRedactor::class)->redactString($errorMessage, $account),
        );
    }

    public function failed(): bool
    {
        return ! $this->successful;
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
            'account_id' => $this->accountId !== null ? app(SecretRedactor::class)->redactString($this->accountId) : null,
            'account_name' => $this->accountName !== null ? app(SecretRedactor::class)->redactString($this->accountName) : null,
            'expires_at' => $this->expiresAt?->format(DATE_ATOM),
            'failure_class' => $this->failureClass?->value,
            'error_code' => $this->errorCode !== null ? app(SecretRedactor::class)->redactString($this->errorCode) : null,
            'error_message' => $this->errorMessage !== null ? app(SecretRedactor::class)->redactString($this->errorMessage) : null,
            'account' => app(SecretRedactor::class)->redactAndTrim($this->account),
        ];
    }
}

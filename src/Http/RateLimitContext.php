<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Http;

final readonly class RateLimitContext
{
    public function __construct(
        public string $driverKey,
        public string $accountFingerprint,
        public ?string $endpointBucket = null,
    ) {}

    public static function forConnection(string $driverKey, ?string $externalAccountId, int|string $connectionId): self
    {
        $account = is_string($externalAccountId) && $externalAccountId !== ''
            ? $externalAccountId
            : 'connection:'.$connectionId;

        return new self(
            driverKey: $driverKey,
            accountFingerprint: hash('sha256', $driverKey.'|'.$account),
        );
    }

    public function cacheKey(): string
    {
        $parts = ['omnichannel-api-rl', $this->driverKey, $this->accountFingerprint];
        if ($this->endpointBucket !== null && $this->endpointBucket !== '') {
            $parts[] = $this->endpointBucket;
        }

        return implode(':', $parts);
    }
}
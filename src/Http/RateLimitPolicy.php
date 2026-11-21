<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Http;

final readonly class RateLimitPolicy
{
    public function __construct(
        public int $maxAttempts,
        public int $decaySeconds,
    ) {}

    public static function forDriver(string $driverKey): self
    {
        /** @var array{attempts?: int, per_seconds?: int} $config */
        $config = config("omnichannel.channels.{$driverKey}.rate_limit", []);
        $max = isset($config['attempts']) ? (int) $config['attempts'] : (int) config('omnichannel.api_rate_limit.default_attempts', 60);
        $decay = isset($config['per_seconds']) ? (int) $config['per_seconds'] : (int) config('omnichannel.api_rate_limit.default_decay_seconds', 60);

        return new self(max(1, $max), max(1, $decay));
    }
}
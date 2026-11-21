<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Services;

use Karnoweb\Omnichannel\Http\RateLimitContext;
use Karnoweb\Omnichannel\Models\ChannelConnection;

final class ApiRequestRateLimitScope
{
    private ?RateLimitContext $current = null;

    public function current(): ?RateLimitContext
    {
        return $this->current;
    }

    /**
     * @template T
     * @param  callable(): T  $callback
     * @return T
     */
    public function using(ChannelConnection $connection, callable $callback, ?string $endpointBucket = null): mixed
    {
        $connection->loadMissing('channel');
        $driverKey = $connection->channel->driver ?? 'unknown';
        $previous = $this->current;
        $this->current = RateLimitContext::forConnection(
            $driverKey,
            $connection->external_account_id,
            $connection->getKey(),
        );
        if ($endpointBucket !== null) {
            $this->current = new RateLimitContext(
                $this->current->driverKey,
                $this->current->accountFingerprint,
                $endpointBucket,
            );
        }

        try {
            return $callback();
        } finally {
            $this->current = $previous;
        }
    }

    /**
     * @template T
     * @param  callable(): T  $callback
     * @return T
     */
    public function usingDriverKey(string $driverKey, ?string $externalAccountId, int|string $connectionId, callable $callback): mixed
    {
        $previous = $this->current;
        $this->current = RateLimitContext::forConnection($driverKey, $externalAccountId, $connectionId);

        try {
            return $callback();
        } finally {
            $this->current = $previous;
        }
    }
}
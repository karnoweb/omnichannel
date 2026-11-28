<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Services;

use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Log;
use Karnoweb\Omnichannel\Exceptions\RateLimitExceededException;
use Karnoweb\Omnichannel\Http\RateLimitContext;
use Karnoweb\Omnichannel\Http\RateLimitPolicy;

final class ApiRequestRateLimiter
{
    public function __construct(private readonly CacheFactory $cache) {}

    public function consume(RateLimitContext $context): void
    {
        if (! (bool) config('omnichannel.api_rate_limit.enabled', true)) {
            return;
        }

        $policy = RateLimitPolicy::forDriver($context->driverKey);
        $storeName = config('omnichannel.api_rate_limit.cache_store');
        $store = is_string($storeName) && $storeName !== ''
            ? $this->cache->store($storeName)
            : $this->cache->store();
        $key = $context->cacheKey();
        $lock = $store->lock($key.':lock', 10);

        try {
            $lock->block(5, function () use ($store, $key, $policy, $context): void {
                $hits = (int) $store->get($key, 0);
                if ($hits >= $policy->maxAttempts) {
                    $ttl = $store->get($key.':ttl');
                    $retryAfter = is_int($ttl) && $ttl > 0
                        ? $ttl
                        : (int) config('omnichannel.queue.rate_limited_fallback_seconds', 300);

                    throw new RateLimitExceededException($context, max(1, $retryAfter));
                }

                if ($hits === 0) {
                    $store->put($key, 1, $policy->decaySeconds);
                    $store->put($key.':ttl', $policy->decaySeconds, $policy->decaySeconds);
                } else {
                    $store->increment($key);
                }
            });
        } catch (LockTimeoutException) {
            throw new RateLimitExceededException(
                $context,
                (int) config('omnichannel.queue.rate_limited_fallback_seconds', 300),
                'Could not acquire rate limit lock; rejecting request to avoid overrun.',
            );
        }

        if (config('cache.default') === 'array' && config('omnichannel.api_rate_limit.warn_on_array_store', true)) {
            Log::debug('omnichannel.api_rate_limit.array_store', ['key' => $key]);
        }
    }
}

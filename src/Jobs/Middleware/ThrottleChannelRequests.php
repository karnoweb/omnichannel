<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Jobs\Middleware;

use Closure;
use Illuminate\Support\Facades\RateLimiter;

final class ThrottleChannelRequests
{
    public function __construct(
        private readonly string $channelKey,
        private readonly ?int $maxAttempts = null,
        private readonly ?int $decaySeconds = null,
    ) {}

    public function handle(object $job, Closure $next): mixed
    {
        $key = 'omnichannel-throttle:'.$this->channelKey;

        $max = $this->maxAttempts ?? (int) config(
            "omnichannel.channels.{$this->channelKey}.rate_limit.attempts",
            60
        );

        $decay = $this->decaySeconds ?? (int) config(
            "omnichannel.channels.{$this->channelKey}.rate_limit.per_seconds",
            60
        );

        if (RateLimiter::tooManyAttempts($key, $max)) {
            return $job->release(RateLimiter::availableIn($key) + 1);
        }

        RateLimiter::hit($key, $decay);

        return $next($job);
    }
}

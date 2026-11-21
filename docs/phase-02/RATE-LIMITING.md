# API request rate limiting

Package defaults are **internal quotas**, not official platform caps.

## Components

| Component | Role |
| --- | --- |
| `ApiRequestRateLimiter` | Atomic consume via cache lock |
| `ApiRequestRateLimitScope` | Per-connection scope for driver operations |
| `RateLimitContext` | Driver + hashed account fingerprint (+ optional bucket) |
| `BaseApiClient` | `beforeSending` hook counts each HTTP attempt |
| `ThrottleChannelRequests` | Job-level throttle (unchanged; complementary) |

## Configuration

`config/omnichannel.php`:

- `api_rate_limit.enabled`
- `api_rate_limit.cache_store` — use `redis` in production multi-worker setups
- Per-channel `channels.*.rate_limit`

## Failure semantics

- Local quota: `ApiResponse::localRateLimited()` (`status=0`, `error=local_rate_limit`)
- Platform quota: HTTP `429` + `Retry-After` via `ApiResponse::retryAfterSeconds()`

## Verification

- Feature tests: `ApiRequestRateLimiterTest`, `ConcurrentRateLimitTest`, `SharedAccountRateLimitTest`, `DriverRequestCountingTest`, `RetryAfterPropagationTest`
- Redis integration: `RedisRateLimitIntegrationTest` (`@group redis`)
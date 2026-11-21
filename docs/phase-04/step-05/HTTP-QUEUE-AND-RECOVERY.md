# HTTP-QUEUE-AND-RECOVERY

## HTTP

- `BaseApiClient` + channel-specific clients
- `ApiResponse` parsing؛ `SecretRedactor` در log
- Rate: `ApiRequestRateLimiter`, `ThrottleChannelRequests` middleware on job

To-Be مشروط DEC-001: هم‌راستایی سقف Instagram با Meta.

## صف

| Job | نقش |
|---|---|
| `PublishPublicationJob` | انتشار |
| `PollWhatsAppCatalogBatchJob` | batch status |
| `DispatchPendingCatalogSyncsJob` | catalog sync |

میزبان: queue connection/name از config.

## خطا و retry

`docs/hardening/RETRY-POLICY.md` — تفکیک safe vs unsafe retry (`UnsafeRequestRetryTest`).

## بازیابی

`PublicationRecoveryService`, `CatalogSyncRecoveryService`, `WhatsAppCatalogReconciliationService` (اختیاری).

## To-Be

- مستندسازی یک جا برای «چه خطایی retry می‌شود» در operations guide (۴.۸).
- Redis rate limit: گروه `@group redis` — integration جدا از CI SQLite.
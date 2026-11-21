# OPERATIONS-AND-OBSERVABILITY

## استقرار (As-Is)

- Laravel package در میزبان
- Queue worker برای `PublishPublicationJob`
- Scheduler: `DispatchDuePublicationsCommand`
- Redis اختیاری برای rate limit (`ConcurrentRateLimitTest` — گروه redis)

## دستورات

| Command | کاربرد |
|---|---|
| `DispatchDuePublicationsCommand` | زمان‌بندی |
| `InspectStuckPublicationsCommand` | ops |
| `RefreshChannelTokensCommand` | توکن |
| `RedactSensitiveHistoryCommand` | امنیت log |
| `SyncMarketplaceTaxonomyCommand` | taxonomy |
| `InspectWhatsAppCatalogBatchCommand` | WA batch |

## پایش

Events: `PublicationPublished`, `PublicationFailed`, batch events — listener میزبان.

## To-Be

health check command استاندارد (FR-036) — طراحی مشروط در ۴.۷.
**بدون ادعای آمادگی تولید** بدون live.
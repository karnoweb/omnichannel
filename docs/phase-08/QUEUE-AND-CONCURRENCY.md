# صف و همزمانی (WP-08-04)

## همگام‌سازی کاتالوگ

- `DispatchPendingCatalogSyncsJob`: `lockForUpdate` روی `CatalogSyncState` هنگام claim
- تست: [CatalogSyncConcurrencyTest](../../tests/Feature/CatalogSyncConcurrencyTest.php)
- revision/fingerprint: [CatalogSyncFingerprint](../../src/Services/CatalogSyncFingerprint.php)

## انتشار

- idempotency کلید در `PublicationCreationService` / batch `intent_id`
- [CatalogSyncRecoveryTest](../../tests/Feature/CatalogSyncRecoveryTest.php) — policy و attempts

## Redis

- job CI: `redis-integration` در `.gitlab-ci.yml`
- تست: [RedisRateLimitIntegrationTest](../../tests/Integration/RedisRateLimitIntegrationTest.php) با `OMNICHANNEL_TEST_REDIS_HOST`
- محلی: `vendor/bin/phpunit --group redis` پس از تنظیم env

## محدودیت

تست بار واقعی چند worker OS در میزبان — خارج از مخزن؛ پکیج قفل cache و DB row lock را در تست feature پوشش می‌دهد.
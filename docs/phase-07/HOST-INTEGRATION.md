# راهنمای یکپارچگی میزبان

پکیج `karnoweb/omnichannel` داده و سرویس دامنه را فراهم می‌کند؛ **UI، رویدادهای تجاری، و منبع حقیقت محصول/قیمت در میزبان** است.

## جدول مسئولیت

| موضوع | پکیج | میزبان |
|-------|------|--------|
| مدل Publication، اتصال کانال، صف انتشار | بله | — |
| UI انتشار، فرم batch، نقش‌ها | — | بله |
| ProductRepository / شعبه | قرارداد | پیاده‌سازی |
| Reference registrar | registry | register در ServiceProvider |
| catalog_sync | job/state | رویداد قیمت/موجودی + metadata اتصال |
| cron صف / doctor | فرمان Artisan | زمان‌بندی سرور |

## ۱. پیش‌نیاز نصب

- PHP `^8.4.1` و Laravel / `illuminate/*` `^13` — [HOST-REQUIREMENTS.md](HOST-REQUIREMENTS.md)
- `composer require karnoweb/omnichannel`
- publish: `php artisan vendor:publish --tag=omnichannel-config` (در صورت نیاز)
- `php artisan migrate` (migration squashed پکیج)
- worker صف برای `PublishPublicationJob`, `DispatchPendingCatalogSyncsJob`, و غیره

## ۲. قراردادهای اجباری / توصیه‌شده

| قرارداد | چه زمانی | ثبت |
|---------|----------|-----|
| `BranchContextResolverContract` | همه عملیات شعبه‌ای | `AppServiceProvider` |
| `ProductRepository` + `BranchScopedProductRepository` | انتشار Product | binding singleton |
| `PublicationSourceResolverContract` | `PublicationSourceType::Reference` | `PublicationSourceRegistry::register` |
| `CatalogSyncTriggerContract` | همگام‌سازی کاتالوگ | جایگزینی `DefaultCatalogSyncTrigger` |
| `PublicationMediaStorageContract` | رسانه غیر local | در صورت نیاز |
| `ActorContextResolverContract` | audit | توصیه |

نمونه‌ها: [docs/examples/host/](../examples/host/).

### منبع Reference (WP-07-01)

1. پیاده‌سازی `PublicationSourceResolverContract` با `logicalType()` ثابت.
2. در `boot` میزبان: `$registry->register($resolver)`.
3. هنگام `PublicationCreationService::create` با `sourceReferenceType` همان logicalType؛ در غیر این صورت `RuntimeException: No resolver registered for reference type [...]`.

## ۳. شعبه و امنیت

- هر `ChannelConnection` به `branch_id` تعلق دارد؛ میزبان باید `OmnichannelContext::runForBranch()` یا resolver شعبه را قبل از سرویس‌ها تنظیم کند.
- بدون شعبه معتبر: `BranchAccessDeniedException`.

## ۴. انتشار و batch

- تک‌انتشار: `PublicationCreationService` یا `PublishingService`.
- گروهی: `PublicationBatchService` + `CreatePublicationBatchData`; `intent_id` پایدار برای idempotency فرم.
- `dispatch_mode = now` پس از commit؛ میزبان transaction خود را رعایت کند.

## ۵. رسانه

- آپلود در میزبان؛ پکیج `PublicationMediaService` و `omc-asset:{id}` برای درایورها.
- راهنمای قدیمی انگلیسی: [HOST-INTEGRATION-PHASE-03.md](../HOST-INTEGRATION-PHASE-03.md).

## ۶. catalog_sync (opt-in)

- `config('omnichannel.catalog_sync.enabled')` پیش‌فرض `false`.
- هر اتصال: `metadata.catalog_sync_enabled = true`.
- میزبان از `CatalogSyncService::recordProductChange` یا `CatalogSyncTriggerContract` با snapshot `price`/`stock`/`currency` فراخوانی کند.
- منبع حقیقت قیمت/موجودی **میزبان** است؛ پکیج snapshot را به provider می‌فرستد.
- قرارداد: [CATALOG-SYNC-CONTRACT.md](../phase-06/CATALOG-SYNC-CONTRACT.md).

## ۷. عملیات تولید

| فرمان | نقش میزبان |
|--------|------------|
| `omnichannel:dispatch-due` | cron |
| `omnichannel:doctor` / `health` | پایش |
| بازیابی catalog | فراخوانی recovery سرویس یا job میزبان (ر. RECOVERY-RUNBOOK) |

## ۸. درایور سفارشی

ر. [CUSTOM-DRIVER-GUIDE.md](CUSTOM-DRIVER-GUIDE.md) — `ChannelManager::extend` یا `config/omnichannel.drivers`.

## ۹. خارج از دامنه این مخزن

- داشبورد مدیریت کانال‌ها
- تست E2E UI
- Webhook دریافت (DEC-008)
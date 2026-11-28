# قرارداد همگام‌سازی کاتالوگ

## فعال‌سازی

- `omnichannel.catalog_sync.enabled` پیش‌فرض `false`.
- هر اتصال باید `metadata.catalog_sync_enabled = true` داشته باشد.
- درایور باید `Capability::PriceAndStock` را پشتیبانی کند.

## ورودی میزبان

میزبان از `CatalogSyncTriggerContract::recordChange()` یا `CatalogSyncService::recordProductChange()` با snapshot شامل `price`، `stock` و در صورت نیاز `currency` فراخوانی می‌کند.

## اجرا

`DispatchPendingCatalogSyncsJob` snapshot را روی `Publication` اعمال می‌کند و `ChannelDriver::update()` را صدا می‌زند.

- `Synced` فقط پس از `PublishResult::successful`.
- `Unknown` برای `FailureClass::UnknownOutcome` (مثلاً batch واتس‌اپ).
- `Failed` برای خطاهای قطعی یا snapshot ناقص.

## خروجی

`CatalogSyncState` شامل `desired_snapshot`، `applied_snapshot` (پس از موفقیت)، `last_synced_at` و `last_error`.
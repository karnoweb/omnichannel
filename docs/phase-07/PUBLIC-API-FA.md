# نمایه API عمومی پکیج (فارسی)

مرجع تفصیلی: [PUBLIC-API-INVENTORY.md](../phase-04/step-01/PUBLIC-API-INVENTORY.md)، معماری: [ARCHITECTURE.md](../../ARCHITECTURE.md).

## سرویس‌های اصلی

| سرویس | کاربرد نمونه |
|--------|----------------|
| `PublishingService` | انتشار، به‌روزرسانی، حذف، غیرفعال‌سازی |
| `PublicationCreationService` | ایجاد از product / free-form / reference |
| `PublicationBatchService` | ایجاد و dispatch دسته‌ای |
| `PublicationMediaService` | ثبت asset و attach |
| `CatalogSyncService` | ثبت تغییر قیمت/موجودی → state |
| `CatalogSyncDispatcher` / `CatalogSyncExecutor` | dispatch و اجرای sync |
| `CatalogSyncRecoveryService` | بازیابی Failed/stale |
| `MarketplaceTaxonomyService` | دسته‌های خارجی (Basalam) |
| `CategoryMappingResolver` | نگاشت دسته داخلی ↔ خارجی |
| `ChannelManager` | resolve درایور (`driver`, `extend`) |
| `PublicationRecoveryService` | انتشار گیرکرده / Unknown |
| `Omnichannel` (Facade) | نقطه ورود کوتاه |

```php
use Karnoweb\Omnichannel\Facades\Omnichannel;

Omnichannel::publish($publication);
```

## قراردادهای میزبان

| قرارداد | نقش |
|---------|-----|
| `ProductRepository` | بارگذاری محصول برای source Product |
| `BranchScopedProductRepository` | ایزوله شعبه محصول |
| `BranchContextResolverContract` | شناسه شعبه جاری |
| `ActorContextResolverContract` | actor برای لاگ |
| `PublicationSourceResolverContract` | منبع Reference |
| `CatalogSyncTriggerContract` | وصل رویداد تجاری به sync |
| `PublicationMediaStorageContract` | ذخیره بایت رسانه |
| `PublicationMediaUrlResolverContract` | URL (legacy) |
| `InternalCategoryResolverContract` | اعتبارسنجی UI mapping |

ثبت Reference:

```php
app(PublicationSourceRegistry::class)->register($myResolver);
```

## درایور و قابلیت

- `ChannelDriver`, `DeactivatableChannelDriver`, `ReconcilableChannelDriver`, `SupportsMarketplaceTaxonomy`
- `Capability` و `UnsupportedCapabilityException` قبل از HTTP

## رویدادها (نمونه)

`PublicationCreated`, `PublicationBatchCreated`, `PublicationBatchDispatched`, `PublicationBatchCompleted` — در `src/Events/`.

## Config مهم

| کلید | پیش‌فرض |
|------|---------|
| `catalog_sync.enabled` | `false` |
| `catalog_sync.debounce_seconds` | عدد از config |
| `publication_batch.max_targets` | 20 |
| `whatsapp_catalog.auto_reconcile` | `false` |
| `omnichannel.drivers.*` | map کلاس درایور |

فایل: `config/omnichannel.php`.

## فرمان‌های Artisan

`omnichannel:dispatch-due`, `inspect-stuck`, `doctor`, `health`, `prune-artifacts`, `prune-media`, `sync-taxonomy`, `refresh-tokens`, `redact-history`, `inspect-whatsapp-catalog-batch`.

## Jobs

`PublishPublicationJob`, `DispatchPendingCatalogSyncsJob`, `PollWhatsAppCatalogBatchJob`, `SyncMarketplaceTaxonomyJob` (در صورت ثبت).

## فاز ۶ — همگام‌سازی

- `CatalogSyncFingerprint`: fingerprint پایدار snapshot
- revision در metadata state؛ `lockForUpdate` در job
- `CatalogSyncReconciliationService`: Unknown → Synced پس از batch WA

مستندات: [phase-06/](../phase-06/).

## مثال ایجاد Reference

پس از registrar:

```php
$data = new CreatePublicationData(
    sourceType: PublicationSourceType::Reference,
    connection: $connection,
    type: PublicationType::Post,
    content: PublicationContent::fromArray(['caption' => '...']),
    sourceReferenceType: 'blog_post',
    sourceReferenceId: '42',
);
$publication = app(PublicationCreationService::class)->create($data);
```
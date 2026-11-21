# PUBLIC-API-INVENTORY

## سرویس‌های اصلی (مصرف میزبان/پکیج)

| کلاس | مسئولیت |
|---|---|
| `PublishingService` | create, publish, update, delete, deactivate |
| `PublicationCreationService` | createFromProduct, createFromContent |
| `PublicationBatchService` | ایجاد/dispatch گروه |
| `PublicationMediaService` | ثبت asset، attach به publication |
| `MarketplaceTaxonomyService` | upsert دسته خارجی |
| `CategoryMappingResolver` | اولویت نگاشت دسته |
| `CatalogSyncService` | enqueue/update state |
| `ChannelManager` | resolve driver |
| `PublicationRecoveryService` | stuck/unknown recovery |
| `Omnichannel` / Facade | façade |

## قراردادهای میزبان

`ProductRepository`, `BranchScopedProductRepository`, `BranchContextResolverContract`, `ActorContextResolverContract`, `MediaBytesResolverContract`, `PublicationSourceResolverContract`, `InternalCategoryResolverContract`, `CatalogSyncTriggerContract`, `PublicationMediaStorageContract`, `PublicationMediaUrlResolverContract`, `ChannelDriver`, `DeactivatableChannelDriver`, `ReconcilableChannelDriver`, `SupportsMarketplaceTaxonomy`.

## رویدادها (نمونه)

`PublicationCreated`, `PublicationBatchCreated`, `PublicationBatchDispatched`, `PublicationBatchStatusChanged`, `PublicationBatchCompleted` — plus رویدادهای انتشار فاز ۱/۲ در `src/Events/`.

## Config کلیدهای فاز ۳

| کلید | پیش‌فرض مهم |
|---|---|
| `publication_media.*` | disk `local` |
| `publication_batch.max_targets` | 20 |
| `catalog_sync.enabled` | **false** |
| `whatsapp_catalog.auto_reconcile` | **false** |

جزییات: `config/omnichannel.php`.

## فرمان‌های Console

| فرمان | کلاس | اثر جانبی |
|---|---|---|
| `omnichannel:dispatch-due` | `DispatchDuePublicationsCommand` | dispatch jobs |
| `omnichannel:inspect-stuck` | `InspectStuckPublicationsCommand` | read-only عمدی |
| `omnichannel:doctor` | `OmnichannelDoctorCommand` | تشخیص |
| `omnichannel:health` | `OmnichannelHealthCommand` | تشخیص |
| `omnichannel:prune-artifacts` | `PruneOmnichannelArtifactsCommand` | حذف منطقی/فیزیکی با flag |
| `omnichannel:prune-media` | `PrunePublicationMediaCommand` | dry-run پیش‌فرض |
| `omnichannel:sync-taxonomy` | `SyncMarketplaceTaxonomyCommand` | HTTP به Basalam |
| `omnichannel:refresh-tokens` | `RefreshChannelTokensCommand` | OAuth |
| `omnichannel:redact-history` | `RedactSensitiveHistoryCommand` | mutate logs |
| `omnichannel:inspect-whatsapp-catalog-batch` | `InspectWhatsAppCatalogBatchCommand` | read |

نام دقیق signature از `->signature` هر کلاس خوانده شود قبل از اجرا در تولید.

## Jobs

`PublishPublicationJob`, `PollWhatsAppCatalogBatchJob`, `DispatchPendingCatalogSyncsJob`, `SyncMarketplaceTaxonomyJob` (در صورت وجود در src/Jobs).
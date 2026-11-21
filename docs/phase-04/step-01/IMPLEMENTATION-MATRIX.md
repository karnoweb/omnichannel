# IMPLEMENTATION-MATRIX — قابلیت‌های پکیج (از کد)

| حوزه | قابلیت | پیاده‌سازی | فعال‌سازی | کیفیت | شاهد کد | تست | اجرا | یافته |
|---|---|---|---|---|---|---|---|---|
| انتشار | Product source | پیاده | پیش‌فرض | سالم | `PublicationCreationService` | LegacyProduct* | بله | — |
| انتشار | FreeForm | پیاده | پیش‌فرض | سالم | همان | FreeForm* | بله | — |
| انتشار | Reference | اسکلت | نیاز registrar | — | `PublicationSourceRegistry` | Validation | بله | OMC-AUD-006 |
| انتشار | زمان‌بندی | پیاده | config | سالم | `DispatchDuePublicationsCommand` | Scheduled* | بله | — |
| رسانه | asset `omc-asset:` | پیاده | disk config | سالم | `PublicationMediaService` | Media* | بله | — |
| رسانه | legacy URL MediaItem | پیاده | — | سالم | `MediaItem`, `HttpMediaBytesResolver` | LegacyMedia* | بله | — |
| Batch | چند اتصال | پیاده | — | سالم | `PublicationBatchService` | Batch* | بله | — |
| Taxonomy | جدول + mapper | جزئی | دستی/command | — | `CategoryMappingResolver` | Category* | بله | OMC-AUD-011 |
| Taxonomy | sync API زنده | اسکلت | command | — | `SyncMarketplaceTaxonomyCommand` | mock محلی | — | OMC-AUD-008 |
| Catalog sync | state machine | اسکلت | **off** | — | `CatalogSyncService` | CatalogSync* | بله | OMC-AUD-010 |
| امنیت | SSRF media | پیاده | — | سالم | `HttpMediaBytesResolver` | HttpMedia* | بله | hardening |
| صف | publish job | پیاده | queue config | سالم | `PublishPublicationJob` | Queue* | بله | — |
| نرخ | ApiRequestRateLimiter | پیاده | enabled default | سالم | `BaseApiClient` | Rate* | بله | redis جدا |
| WhatsApp | reconcile | پیاده | **off** | — | `WhatsAppCatalogReconciliationService` | WhatsApp* | بله | — |

## درایور × نوع انتشار (خلاصه)

| درایور | Post | Product | Carousel/Reel | تست DriverContract |
|---|---|---|---|---|
| demo | بله | بله | — | بله |
| instagram | بله | خیر | بله | بله |
| telegram/bale | بله | خیر | آلبوم | بله |
| basalam | محدود | بله | — | بله |
| whatsapp | خیر | catalog | — | بله |
| rubika/gap/eitaa | بله | محدود | — | بله |

**تست اجراشده:** `tests/Drivers/DriverContractTest.php` در suite کامل 102.
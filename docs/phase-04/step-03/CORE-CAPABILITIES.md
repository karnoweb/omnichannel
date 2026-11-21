# CORE-CAPABILITIES — ماتریس هسته

وضعیت رسمی سرویس خارجی برای این بخش **نامرتبط با دلیل** است، مگر وابستگی صریح به درایور.

| OMC-CORE | قابلیت داخلی | ورودی | قرارداد | وابستگی خارجی | پیاده‌سازی | تست | شکاف |
|---|---|---|---|---|---|---|---|
| OMC-CORE-001 | ثبت/انتخاب درایور | `ChannelManager`, config | `ChannelDriver` | ندارد | کامل | Contract | — |
| OMC-CORE-002 | منبع Product | `PublicationCreationService` | enum source | درایور product | کامل | Feature | — |
| OMC-CORE-003 | منبع Reference | registry | `PublicationSourceRegistry` | registrar میزبان | اسکلت | Validation | OMC-AUD-006 |
| OMC-CORE-004 | انتشار گروهی | `PublicationBatchService` | batch API | per-driver | کامل | Batch* | — |
| OMC-CORE-005 | رسانه `omc-asset:` | `PublicationMediaService` | disk | HTTP fetch | کامل | Media* | — |
| OMC-CORE-006 | Taxonomy mapping | `MarketplaceTaxonomyService` | mapper table | Basalam API اختیاری | جزئی | Category* | OMC-AUD-008 |
| OMC-CORE-007 | Catalog sync | `CatalogSyncService` | policy | WhatsApp/Basalam | جزئی، **off** | CatalogSync* | OMC-AUD-010 |
| OMC-CORE-008 | WA reconcile | `WhatsAppCatalogReconciliationService` | command | WA catalog | کامل، اختیاری | WhatsApp* | — |
| OMC-CORE-009 | Rate limit | `ApiRequestRateLimiter` | config | همه HTTP | کامل | Rate* | P43-GAP-002 |
| OMC-CORE-010 | SSRF guard | `HttpMediaBytesResolver` | URL rules | fetch | کامل | HttpMedia* | — |
| OMC-CORE-011 | Queue publish | `PublishPublicationJob` | queue | درایور | کامل | Queue* | redis گروه جدا |
| OMC-CORE-012 | مرز میزبان قیمت/موجودی | `CatalogSyncPolicy` | host authority | همه commerce | کامل | Policy tests | OMC-AUD-012 |

## تفاوت با IMPLEMENTATION-MATRIX (۴.۱)

این سند ردیف‌های `OMC-CORE-*` و وابستگی درایور را صریح می‌کند؛ ماتریس ۴.۱ سطح بالاتر بود و تکرار نشده است.

## وابستگی به درایور (نمونه)

| OMC-CORE | درایورهای وابسته |
|---|---|
| OMC-CORE-007 | `whatsapp`, `basalam` (اسکلت) |
| OMC-CORE-008 | `whatsapp` |
| OMC-CORE-006 | عمدتاً `basalam` |
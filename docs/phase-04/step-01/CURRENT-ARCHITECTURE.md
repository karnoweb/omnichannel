# معماری فعلی — 13.3.0

## مرز پکیج و میزبان

```mermaid
flowchart TB
  Host[Host_karno_base]
  Pkg[Omnichannel_Package]
  Host -->|Contracts_DTO_Events| Pkg
  Pkg -->|HTTP_APIs| Providers[External_Providers]
  Host -.->|Product_Branch_User_UI| Host
```

میزبان: `ProductRepository`, `BranchContextResolverContract`, `ActorContextResolverContract`, رسانه/آداپتور اختیاری، listener قیمت/موجودی برای catalog sync.

## ورود و DI

| نقطه | مسیر |
|---|---|
| ServiceProvider | `src/OmnichannelServiceProvider.php` |
| Facade | `Karnoweb\Omnichannel\Facades\Omnichannel` |
| سرویس ریشه | `src/Omnichannel.php` — drivers, publishing, logger, context |
| درایورها | `config/omnichannel.php` → `ChannelManager` |

## جریان انتشار (خلاصه)

```mermaid
stateDiagram-v2
  [*] --> Draft
  Draft --> Scheduled: schedule
  Draft --> Publishing: dispatch_now
  Scheduled --> Publishing: due_job
  Publishing --> Published: success
  Publishing --> Failed: failure
  Publishing --> Unknown: ambiguous_outcome
  Published --> Publishing: update_republish
  Failed --> Publishing: retry_policy
```

- ایجاد: `PublishingService` → `PublicationCreationService` (Product/FreeForm)
- ارسال: `PublishPublicationJob` → `ChannelDriver`
- Batch: `PublicationBatchService` → چند `Publication` مستقل
- Catalog: `CatalogSyncTriggerContract` → `CatalogSyncDispatcher` (اگر فعال)

## جداول (پیش‌فرض پیشوند `omc_`)

| کلید config | جدول | فاز |
|---|---|---|
| channels, connections, publications, publication_logs | اصلی | ۱ |
| — | publication media/batches/taxonomy/catalog | ۳ (`2026_11_21_000002`–`000005`) |

ستون‌های جدید انتشار: `source_type`, `source_reference_*`, `source_title`; `product_id` nullable (migration `000001`).

## قراردادهای میزبان (نمونه)

`ProductRepository`, `PublicationSourceResolverContract`, `InternalCategoryResolverContract`, `CatalogSyncTriggerContract`, `PublicationMediaStorageContract`, `PublicationMediaUrlResolverContract`.

جزئیات API عمومی: [PUBLIC-API-INVENTORY.md](PUBLIC-API-INVENTORY.md).
# CATALOG-ARCHITECTURE

## مالکیت (P-04)

میزبان = source of truth قیمت/موجودی. `CatalogSyncPolicy` مانع overwrite ناخواسته.

## فعال‌سازی (P-05)

```text
catalog_sync.enabled (global false) AND connection.metadata.catalog_sync_enabled
```

شواهد: `config/omnichannel.php`, `CatalogSyncDisabledTest`.

## نگاشت دسته (phase-03 اولویت)

override → DB `CategoryMapping` → connection metadata → config → default id — `CategoryMappingResolver`.

## هویت خارجی

`CatalogListingIdentity`, `CatalogSyncState` model.

## WA vs Basalam

- WA: batch async + reconcile command
- Basalam: REST product + file upload

## To-Be

- مستند trigger میزبان برای sync (بدون فعال‌سازی خودکار ارتقا)
- تأیید status basalam (DEC / P43-GAP-004) در data mapping layer
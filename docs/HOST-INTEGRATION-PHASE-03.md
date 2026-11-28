# Host integration — Phase 03

**راهنمای اصلی (فاز ۷، فارسی):** [docs/phase-07/HOST-INTEGRATION.md](phase-07/HOST-INTEGRATION.md) و [HOST-INTEGRATION-CHECKLIST.md](phase-07/HOST-INTEGRATION-CHECKLIST.md). این فایل خلاصه انگلیسی مرحله ۰۳ است.

## Bind host contracts

- `ProductRepository` / `BranchScopedProductRepository` for product-sourced publications and batches.
- `PublicationSourceResolverContract` registrations in `PublicationSourceRegistry` for reference sources (e.g. blog posts).
- `InternalCategoryResolverContract` when validating category mappings in the host UI.
- `CatalogSyncTriggerContract` (default: `DefaultCatalogSyncTrigger`) to enqueue catalog sync when price/stock changes.

## Media

- Implement `PublicationMediaStorageContract` / `PublicationMediaUrlResolverContract` if the host does not use the default local disk adapter.
- Host UI uploads bytes; package stores metadata and resolves `omc-asset:{id}` for drivers.

## Catalog sync

- Keep `catalog_sync.enabled` **false** until the host wires product change events and connection-level `metadata.catalog_sync_enabled`.
- Never treat Omnichannel as source of truth for price or stock.

## Batches

- Call `PublicationBatchService::create()` with `CreatePublicationBatchData` and per-target `PublicationTargetData`.
- Dispatch happens after commit for `dispatch_mode = now`; host must provide stable `intent_id` for idempotent form submits.
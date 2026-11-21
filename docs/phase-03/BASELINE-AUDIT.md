# Phase 03 — Baseline Audit

Date: 2026-11-21  
Package: `karnoweb/omnichannel` 13.2.0 (pre-Phase 03)  
Scope: `D:/my-packages/omnichannel` only

## Verification

| Check | Result |
| --- | --- |
| Package tests (`--exclude-group redis`) | OK (79 tests, 8911 assertions) |
| Host code changes | Out of scope for this phase |
| MySQL/PostgreSQL | Not run |
| Live APIs | Not run |

## Gaps for Phase 03

| Area | Current state |
| --- | --- |
| Publication sources | `product_id` required; only product path in `PublishingService::create` |
| Multi-connection | One publication per `create` call |
| Dedicated media | `MediaItem` URL-only; no `media_assets` table |
| Taxonomy | `BasalamCategoryMapper` config/metadata only |
| Catalog sync | No `catalog_sync_states`; WhatsApp/Basalam manual publish only |
| Batch coordinator | Not present |
| Reference sources | Not present |

## Host read-only notes (karno-base)

- `ShopProductRepository` implements `BranchScopedProductRepository`
- `PublicationComposer` is product-only
- No dedicated Omnichannel price/stock change event found — `CatalogSyncTriggerContract` planned in package

## Carried risks from Phase 02

- Host integration and live channel validation remain Pending Verification
- Catalog auto-reconcile (WhatsApp) remains disabled by default

## STEP 00 result

Baseline complete. Next: STEP 01 Publication Sources.
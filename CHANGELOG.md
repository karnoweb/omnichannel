# Changelog

## 13.3.0 — 2026-11-21

### Added

- Publication sources (product, free-form, reference) with nullable `product_id` and legacy-compatible idempotency.
- Dedicated publication media (`PublicationMediaAsset`, storage contracts, `omnichannel:prune-media`).
- Multi-connection publication batches (`PublicationBatch`, `PublicationBatchService`, batch events).
- Marketplace taxonomy tables, `CategoryMappingResolver`, and `omnichannel:sync-taxonomy`.
- Catalog sync state layer (`catalog_sync` config disabled by default) and `DispatchPendingCatalogSyncsJob`.
- Phase 03 documentation and host integration guide.

### Changed

- `BasalamCategoryMapper` prefers database category mappings before config/metadata defaults.
- `MediaItem` supports optional `asset_id` and `checksum` in `fromArray`/`toArray`.

## 13.2.0 — 2026-11-20

### Added

- Per-request API rate limiting (`ApiRequestRateLimiter`, scoped via `BaseApiClient`).
- Publication recovery service and recovery log operation.
- WhatsApp catalog reconciliation service and polling job (auto-reconcile disabled by default).
- Commands: `omnichannel:doctor`, `omnichannel:health`, `omnichannel:prune-artifacts`.
- Phase 02 documentation and GitLab CI pipeline.

### Fixed

- Update/delete/deactivate paths use correct connection in API rate limit scope.
- Rate limiter lock closure captures rate limit context.

### Known limitations

- SQLite-only DB verification in this release.
- Live channel and full host validation pending operational environment.
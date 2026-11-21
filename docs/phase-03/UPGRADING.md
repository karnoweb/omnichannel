# Upgrading to 13.3.0

1. Run squashed migrations (fresh columns on `publications` included in base schema; upgrade path migration `2026_11_21_000001` is idempotent).
2. Publish config if you customize table names or models; new keys: `publication_media`, `publication_batch`, `catalog_sync`.
3. No behavior change for `PublishingService::create()` product flow.
4. Enable catalog sync only after host integration (`catalog_sync.enabled` and connection metadata).
5. Optional commands: `omnichannel:prune-media --execute`, `omnichannel:sync-taxonomy {connection}`.
# Phase 03 — Decisions

- Publication remains the unit of state; batches coordinate only.
- `omc-asset:{id}` URIs resolve bytes via `PublicationMediaStorageContract`, not public HTTP by default.
- Category mapping priority: explicit override → DB mapping → connection metadata → config → default category id.
- Catalog sync is opt-in globally and per connection; no automatic enable on upgrade.
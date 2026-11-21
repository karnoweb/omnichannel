# Database compatibility

## Verified

| Engine | Fresh install | Upgrade path | Rollback sample |
| --- | --- | --- | --- |
| SQLite | Yes (`FreshInstallationTest`) | Yes (`UpgradeFrom130Test`, history repair) | Yes (`MigrationRollbackTest`) |

## Not executed (this iteration)

| Engine | Status |
| --- | --- |
| MySQL | Integration Required — not run |
| PostgreSQL | Integration Required — not run |

Custom table prefix, int/uuid/ulid key strategies remain configurable via `config/omnichannel.php`; dedicated compatibility tests should extend `SoftKeyCompatibilityTest` when engines are available.
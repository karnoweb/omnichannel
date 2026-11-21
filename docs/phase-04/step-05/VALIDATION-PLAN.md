# VALIDATION-PLAN

## پوشش موجود (شاهد)

| گروه | تست نمونه | محیط |
|---|---|---|
| Architecture | `NoHostDependencyTest`, `PackageIsolationTest` | SQLite |
| Publication | `PublicationStateMachineTest`, `UnknownOutcomeTest` | SQLite |
| Batch | `PublicationBatchPartialFailureTest` | SQLite |
| Security | `HttpMediaBytesResolverSecurityTest` | SQLite |
| Catalog | `CatalogSyncPolicyTest` | SQLite |
| Drivers | `DriverContractTest` | mock HTTP |
| Redis rate | `RedisRateLimitIntegrationTest` | **redis group** |

جمع مرجع: 102 tests (step-04).

## سناریوهای آینده (طرح — اجرا نشده)

| ID | سناریو | FR/NFR | پیش‌شرط |
|---|---|---|---|
| VAL-001 | ارتقا 13.2→13.3 کامل | FR-038 | migrations |
| VAL-002 | MySQL publication FK | NFR-010 | CI DB |
| VAL-003 | Live IG publish | MET | حساب Meta |
| VAL-004 | Bale method parity | ADR-005 | doc BL |
| VAL-005 | catalog sync enabled path | FR-028 | host trigger |

## معیار پذیرش فنی معماری

طراحی To-Be برای هر FR ضروری دارای سند + ADR یا برچسب مشروط — برآورده در CHECKLIST.
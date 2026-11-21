# MIGRATION-AND-COMPATIBILITY

## As-Is مسیر مهاجرت

Squashed: `database/migrations_squashed/` — از schema پایه تا `2026_11_21_000005`.

| migration | محتوا |
|---|---|
| 2026_11_19 | schema اصلی |
| 2026_11_20 | attempt_token, history, retry |
| 2026_11_21_000001..005 | sources, media, batch, taxonomy, catalog_sync |

شواهد جزئی: `UpgradeFrom130Test` — شکاف FR-038 / OMC-AUD-007.

## سازگاری مصرف‌کننده

- PHP ^8.3, illuminate ^13 — DEC-007
- بدون breaking در 13.3.0 نسبت به قراردادهای عمومی اعلام‌شده در PRD

## دوره گذار To-Be (پیشنهادی)

| تغییر احتمالی | استراتژی |
|---|---|
| Graph v27 | config version bump؛ بدون schema |
| rate limit IG | config only |
| Reference registrar | host-only؛ بدون migration پکیج |
| catalog sync on | config + metadata؛ بدون auto-enable on upgrade |

## بازیابی

Migration rollback tests: `MigrationRollbackTest` — محدود SQLite.
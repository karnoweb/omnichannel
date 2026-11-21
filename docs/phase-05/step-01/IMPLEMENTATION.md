# IMPLEMENTATION — گام ۵.۱

## تغییرات کد

### `tests/Feature/UpgradeFrom130Test.php`

| تست | هدف |
|-----|------|
| `test_history_retention_migrations_apply_on_upgrade` | حفظ رفتار قبلی ۱۱_۲۰ |
| `test_thirteen_three_migrations_reapply_after_rollback` | اجرای مجدد `2026_11_21_*` پس از `down()` |
| `test_publication_sources_migration_is_idempotent_on_upgrade` | idempotent بودن `000001` |

## زیرساخت مشترک (بازبینی فقط)

مرزها در [SHARED-DRIVER-BASELINE.md](SHARED-DRIVER-BASELINE.md) ثبت شد؛ اصلاح کد مشترک در این گام **انجام نشد** (شکست تستی اثبات نشد).

## مستندات

- سه سند SHARED + گزارش‌های این پوشه.
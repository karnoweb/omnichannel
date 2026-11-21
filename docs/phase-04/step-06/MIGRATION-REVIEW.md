# MIGRATION-REVIEW — مهاجرت پایگاه داده

مسیر: `database/migrations_squashed/`. نام فایل **تاریخ مهاجرت را تقریب می‌زند**، نه تاریخ قطعی انتشار نسخه.

## فهرست و اثر

| فایل | نسخه هدف (منطقی) | اثر | نصب تازه | ارتقا | بازگشت (`down`) |
|------|------------------|-----|----------|-------|-----------------|
| `2026_11_19_000000_create_omnichannel_schema.php` | پایه ≤13.2 | schema کامل اولیه | بله | فقط اگر DB خالی | خطر بالا |
| `2026_11_20_000001_add_attempt_token_to_publications.php` | 13.2 / hardening | `attempt_token` nullable | در schema جدید ادغام یا جدا | idempotent اعلام‌شده در راهنماها | بررسی قبل از prod |
| `2026_11_20_000002_preserve_publication_history.php` | 13.2 | FK و null publication_id در log | بله | **MySQL/PG:** باگ تاریخی BASE-01 — رفع در فاز ۰۲ | SQLite محدودیت |
| `2026_11_20_000003_repair_publication_history_constraints.php` | 13.2 | اصلاح قیود | ارتقا | بله | محدود |
| `2026_11_20_000004_add_publication_retry_deadline.php` | 13.2 | مهلت retry | بله | بله | محدود |
| `2026_11_21_000001_publication_sources.php` | 13.3 | منابع انتشار | بله | **idempotent** (phase-03) | داده منابع |
| `2026_11_21_000002_publication_media.php` | 13.3 | جداول/ستون‌های رسانه | بله | بله | رسانه |
| `2026_11_21_000003_publication_batches.php` | 13.3 | batch | بله | بله | batch rows |
| `2026_11_21_000004_marketplace_taxonomy.php` | 13.3 | taxonomy | بله | بله | نگاشت‌ها |
| `2026_11_21_000005_catalog_sync_states.php` | 13.3 | وضعیت همگام‌سازی | بله | بله | state rows |

## تفکیک سناریو

### نصب تازه

1. `composer require` + `vendor:publish` (در صورت نیاز)
2. `php artisan migrate` — اجرای کل squashed روی DB خالی
3. پیش‌فرض: `catalog_sync.enabled = false`

### ارتقا به 13.3.0

1. پشتیبان DB و توقف/تخلیه workerهای omnichannel
2. `composer update` به 13.3.0 (خارج از دامنه این مرحله)
3. `php artisan migrate` — فقط migrationهای جدید نسبت به نصب قبلی
4. بازبینی میزبان: `docs/HOST-INTEGRATION-PHASE-03.md`

### بازگشت (rollback)

- کاهش نسخه کد **بدون** بازگردانی migration می‌تواند schema/کد را ناهمگون کند.
- `down()` روی SQLite در تست‌های پکیج پوشش دارد؛ **تولید MySQL/PG تأیید نشده** در ۴.۶.
- بازگشت به نسخه قبل از تغییرات واتس‌اپ/UnknownOutcome انتشارات خارجی را برنمی‌گرداند.

## ارجاع

- [step-05/MIGRATION-AND-COMPATIBILITY.md](../step-05/MIGRATION-AND-COMPATIBILITY.md)
- [hardening/UPGRADE-GUIDE.md](../../hardening/UPGRADE-GUIDE.md)
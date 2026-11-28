# راهنمای ارتقا — karnoweb/omnichannel

راهنمای مرکزی فارسی. تاریخچه تغییرات: [CHANGELOG.md](CHANGELOG.md). الزام PHP/Laravel میزبان: [docs/phase-07/HOST-REQUIREMENTS.md](docs/phase-07/HOST-REQUIREMENTS.md).

راهنمای کوتاه انگلیسی فاز ۰۳ (دست‌نخورده): [docs/phase-03/UPGRADING.md](docs/phase-03/UPGRADING.md).

---

## انتخاب مسیر

| وضعیت شما | مسیر |
|-----------|------|
| نصب تازه | [نصب تازه 13.3.0](#نصب-تازه-1330) |
| از 13.4.0 | بدون اقدام نسخه‌ای؛ مراقبت عملیاتی |
| از 13.3.1 | [ارتقا به 13.4.0](#ارتقا-به-1340) |
| از 13.3.0 | ابتدا 13.3.1 سپس 13.4.0 توصیه می‌شود |
| از 13.2.0 | [ارتقا به 13.3.0](#ارتقا-به-1330) |
| از 13.1.0 یا 13.0.0 | ابتدا به 13.2.0 سپس 13.3.0 — **ارتقای مستقیم چندپله تأیید نشده** |
| مقاوم‌سازی قدیمی | [docs/hardening/UPGRADE-GUIDE.md](docs/hardening/UPGRADE-GUIDE.md) |

---

## جدول سازگاری مسیرها

| نسخه مبدا | نسخه مقصد | PHP/Laravel | قرارداد | config | DB | صف | میزبان | تست ارتقا | وضعیت |
|-----------|-----------|-------------|---------|--------|-----|-----|--------|-----------|--------|
| — | 13.3.0 (نصب تازه) | ^8.4.1 / ^13 | کامل | publish اختیاری | squashed یک‌جا | پس از migrate | [HOST-INTEGRATION](docs/phase-07/HOST-INTEGRATION.md) | migrate در dev | **تأیید dev** |
| 13.2.0 | 13.3.0 | ^8.4.1 / ^13 | بدون breaking اعلامی | کلیدهای 13.3 | 5 migration | catalog job | کاتالوگ | UpgradeFrom130Test پوشش `2026_11_21_*` (فاز ۵.۱) | **مشروط** |
| 13.1.0 | 13.2.0 | ^8.4.1 / ^13 | — | — | 11-20 | — | — | **تأیید نشده** | مستند تاریخی |
| 13.1.0 | 13.3.0 | — | — | — | تراکم | — | — | **تأیید نشده** | توصیه: پله‌ای |
| 13.3.1 | 13.4.0 | ^8.4.1 / ^13 | گسترش catalog/docs | — | بدون migration جدید | همان | همان | 158 تست SQLite + GHA MySQL/Redis | **نامزد انتشار** |

جزئیات ماتریس: [docs/phase-04/step-06/COMPATIBILITY-MATRIX.md](docs/phase-04/step-06/COMPATIBILITY-MATRIX.md).

---

## پیش از هر ارتقا

1. نسخه نصب‌شده (`composer show karnoweb/omnichannel`) و PHP/Laravel میزبان
2. پشتیبان پایگاه داده و فایل‌های رسانه/آرتیfact مرتبط
3. توقف یا تخلیه workerهای صف Omnichannel
4. انتشارات در جریان، batchهای مبهم واتس‌اپ، قفل‌ها — [hardening/UPGRADE-GUIDE.md](docs/hardening/UPGRADE-GUIDE.md)
5. محیط آزمایشی با همان موتور DB تولید (در صورت امکان)

---

## نصب تازه 13.3.0

```bash
composer require karnoweb/omnichannel
php artisan vendor:publish --tag=omnichannel-config
php artisan migrate
```

- `catalog_sync.enabled` پیش‌فرض **false** بماند تا یکپارچگی میزبان آماده شود.
- تست پیشنهادی: `vendor/bin/phpunit --exclude-group redis` در محیط dev.

---

## ارتقا به 13.4.0

از **13.3.1** (یا 13.3.0 پس از migrate کامل):

1. `composer update karnoweb/omnichannel` به `^13.4`
2. بدون migration اجباری جدید نسبت به 13.3.1
3. فعال‌سازی `catalog_sync` همچنان opt-in — [docs/phase-06/CATALOG-SYNC-CONTRACT.md](docs/phase-06/CATALOG-SYNC-CONTRACT.md)
4. میزبان: [docs/phase-07/HOST-INTEGRATION.md](docs/phase-07/HOST-INTEGRATION.md)
5. تست: `vendor/bin/phpunit --exclude-group redis`

جزئیات: [docs/releases/13.4.0.md](docs/releases/13.4.0.md).

---

## ارتقا به 13.3.0

از **13.2.0** (یا نصب قبلی با migrationهای 11-20 اجراشده):

1. پشتیبان و توقف worker (بالا)
2. `composer update karnoweb/omnichannel` — tag منبع را مالک مشخص می‌کند
3. `php artisan migrate` — شامل `2026_11_21_000001`..`000005` در صورت نیاز
4. اگر config را publish کرده‌اید، کلیدهای جدید را با پیش‌فرض پکیج هماهنگ کنید: `publication_media`, `publication_batch`, `catalog_sync`
5. میزبان: [docs/HOST-INTEGRATION-PHASE-03.md](docs/HOST-INTEGRATION-PHASE-03.md)

### مطابق فاز ۰۳ (انگلیسی، معتبر)

1. Squashed migrations؛ مسیر ارتقا `2026_11_21_000001` **idempotent**
2. بدون تغییر رفتار اعلام‌شده برای `PublishingService::create()` در جریان محصول
3. همگام‌سازی کاتالوگ فقط پس از `catalog_sync.enabled` و metadata اتصال
4. اختیاری: `omnichannel:prune-media --execute`, `omnichannel:sync-taxonomy {connection}` — **فقط پس از درک حذف/تأثیر**

جزئیات migration: [docs/phase-04/step-06/MIGRATION-REVIEW.md](docs/phase-04/step-06/MIGRATION-REVIEW.md).

---

## پس از ارتقا

- `php artisan omnichannel:doctor` / `omnichannel:health` (بدون فرض تأیید کانال زنده)
- مسیر انتشار قدیمی و جدید منابع؛ batch و رسانه
- worker و scheduler؛ idempotency و قفل‌ها
- درایورها: [docs/providers/](docs/providers/)

---

## بازگشت (rollback)

- کاهش نسخه `composer` **بدون** migrate برگشت می‌تواند schema را جلوتر از کد نگه دارد.
- قبل از `migrate:rollback` محتوای `down()` و خطر حذف داده را ببینید — تولید MySQL/PG در ۴.۶ **تأیید نشده**.
- عملیات انجام‌شده روی API خارجی (انتشار، کاتالوگ) با rollback محلی برنمی‌گردد.

---

## مستندات نسخه

- [13.2.0](docs/releases/13.2.0.md) — [13.3.0](docs/releases/13.3.0.md) — [13.4.0](docs/releases/13.4.0.md)
- شناسه‌های تغییر: [CHANGE-EVIDENCE.md](docs/phase-04/step-06/CHANGE-EVIDENCE.md)
## حداقل PHP در تغییرات منتشرنشده

حداقل نسخهٔ مورد نیاز برای اجرای بسته و ابزارهای آزمون PHP 8.4.1 است. پیش از نصب وابستگی‌ها، نسخهٔ PHP محیط اجرا و کارگر صف را کنترل کنید. برای بررسی وابستگی‌های قفل‌شده در محیط توسعه، فرمان `composer check-platform-reqs --lock` را اجرا کنید.

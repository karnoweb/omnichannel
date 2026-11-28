# تاریخچه تغییرات (Changelog)

پکیج `karnoweb/omnichannel`. متن فارسی با حفظ نام کلاس‌ها، فرمان‌ها و کلیدهای config به انگلیسی.

جزئیات هر نسخه: [docs/releases/](docs/releases/README.md). راهنمای ارتقا: [UPGRADING.md](UPGRADING.md).

---

## Unreleased — تغییرات منتشرنشده

- حداقل PHP مورد نیاز به 8.4.1 افزایش یافت.

- نهایی‌سازی دسته واتس‌اپ، موفقیت را فقط با شناسهٔ منطبق و شواهد معتبر بدون خطای قلم اعلام می‌کند.
- آزمون اتصال ناموفق نتیجهٔ خطا برمی‌گرداند و با متغیر تعریف‌نشده متوقف نمی‌شود.
- `CatalogSyncExecutor` و `CatalogSyncSnapshotApplier`: همگام‌سازی قیمت/موجودی از snapshot میزبان؛ وضعیت `Synced` فقط پس از تأیید provider؛ batch واتس‌اپ → `Unknown`.
- `CatalogSyncFingerprint`, revision/debounce, `lockForUpdate` در job، بازیابی با فیلتر policy.
- `CatalogSyncReconciliationService`: همگرایی `Unknown` پس از تأیید batch واتس‌اپ.
- `BasalamDriver` implements `SupportsMarketplaceTaxonomy`; شمارش catalog در `omnichannel:doctor`.


---

## [13.3.1] — 2026-11-21

- **وضعیت:** انتشار PATCH — بدون تغییر قرارداد عمومی درایور.
- **راهنمای ارتقا:** [UPGRADING.md](UPGRADING.md) — [docs/releases/13.3.1.md](docs/releases/13.3.1.md)

### اصلاح‌شده (Fixed)

- پوشش تست مسیر ارتقا برای migrationهای `2026_11_21_000001` تا `000005` در `UpgradeFrom130Test` — OMC-AUD-007 / FR-038

### افزوده‌شده (Added)

- تست‌های قرارداد درایور: `InstagramCapabilitiesTest`, `BaleBotApiParseModeTest`؛ گسترش `DriverContractTest`
- مستندات فاز ۴ (۴.۵–۴.۸) و گزارش‌های اجرای فاز ۵ در `docs/phase-04/` و `docs/phase-05/`
- `CONTRIBUTING.md`, `ROADMAP.md`, `ARCHITECTURE.md`, `UPGRADING.md` (نگاشت فاز ۴)، `docs/releases/`

### تغییرکرده (Changed)

- `UPGRADING.md`: وضعیت تست ارتقا ۱۳.۲→۱۳.۳
- `README.md`: پیوند به گزارش‌های فاز ۵

### حذف از مخزن (Chore)

- پوشه `roadmap/` از Git حذف و در `.gitignore` نگه‌داری محلی پرامپت‌های اجرا

---

## [13.3.0] — 2026-11-21

- **وضعیت:** نسخه ثبت‌شده در `composer.json` و CHANGELOG؛ انتشار عمومی (tag/Packagist) در مخزن محلی **نامشخص** — [HISTORY-GAPS.md](docs/phase-04/step-06/HISTORY-GAPS.md)
- **خلاصه:** منابع انتشار، رسانه اختصاصی، انتشار گروهی، taxonomy بازارگاه و پایه همگام‌سازی کاتالوگ (پیش‌فرض غیرفعال).
- **راهنمای ارتقا:** [UPGRADING.md#ارتقا-به-1330](UPGRADING.md) — [docs/releases/13.3.0.md](docs/releases/13.3.0.md)
- **سازگاری:** PHP ^8.3، Laravel (Illuminate) ^13 — تست پکیج SQLite: **102** تست (گزارش تاریخی فاز ۰۳ و ممیزی ۴.۱)

### افزوده‌شده (Added)

- منابع انتشار (product، free-form، reference) با `product_id` اختیاری و سازگاری idempotency قدیمی — `REL-CHANGE-010`
- رسانه اختصاص انتشار (`PublicationMediaAsset`، قراردادهای storage، `omnichannel:prune-media`) — `REL-CHANGE-011`
- انتشار گروهی چند اتصال (`PublicationBatch`, `PublicationBatchService`, رویدادهای batch) — `REL-CHANGE-012`
- جداول taxonomy بازارگاه، `CategoryMappingResolver`، `omnichannel:sync-taxonomy` — `REL-CHANGE-013`
- لایه `catalog_sync` در config (**`catalog_sync.enabled` پیش‌فرض `false`**) و `DispatchPendingCatalogSyncsJob` — `REL-CHANGE-014`
- مستندات فاز ۰۳ و `docs/HOST-INTEGRATION-PHASE-03.md` — `REL-CHANGE-017`

### تغییرکرده (Changed)

- `BasalamCategoryMapper` اولویت نگاشت دسته در پایگاه داده نسبت به config/metadata — `REL-CHANGE-015`
- `MediaItem` پشتیبانی اختیاری `asset_id` و `checksum` در `fromArray`/`toArray` — `REL-CHANGE-016`

### اصلاح‌شده (Fixed)

- موردی در CHANGELOG قبلی 13.3 ثبت نشده.

### منسوخ‌شده / حذف‌شده / امنیت

- موردی ثبت نشده.

### تغییرات ناسازگار (Breaking Changes)

- موردی در CHANGELOG اعلام نشده؛ مسیر محصول `PublishingService::create()` بدون تغییر رفتار اعلام‌شده در راهنمای فاز ۰۳.

### مهاجرت و اقدام مصرف‌کننده (Upgrade & Migration)

- اجرای migrationهای `2026_11_21_000001` تا `000005`؛ جزئیات [MIGRATION-REVIEW.md](docs/phase-04/step-06/MIGRATION-REVIEW.md)

### محدودیت‌های شناخته‌شده (Known Limitations)

- تأیید MySQL/PostgreSQL، Redis چند worker، میزبان کامل و API زنده خارج از دامنه گزارش‌های تاریخی — [VERIFICATION-STATUS.md](docs/phase-04/step-06/VERIFICATION-STATUS.md)

### وضعیت اعتبارسنجی (Verification)

- گزارش تاریخی: 102 tests، 12738 assertions (`vendor/bin/phpunit --exclude-group redis`)
- مرحله ۴.۶: PHPUnit مجدد اجرا نشد.

### منابع

- [docs/releases/13.3.0.md](docs/releases/13.3.0.md) — [CHANGE-EVIDENCE.md](docs/phase-04/step-06/CHANGE-EVIDENCE.md)

---

## [13.2.0] — 2026-11-20

- **وضعیت:** نسخه تاریخی گزارش‌شده در CHANGELOG؛ انتشار عمومی **نامشخص**
- **خلاصه:** محدودیت نرخ API، بازیابی انتشار، تطبیق کاتالوگ واتس‌اپ، فرمان‌های عملیاتی، مهاجرت‌های hardening.
- **راهنمای ارتقا:** [UPGRADING.md](UPGRADING.md) — [docs/releases/13.2.0.md](docs/releases/13.2.0.md)
- **سازگاری:** PHP ^8.3، Laravel ^13 — تست پکیج SQLite: **79** تست (گزارش فاز ۰۲)

### افزوده‌شده (Added)

- محدودیت نرخ هر درخواست API (`ApiRequestRateLimiter` در `BaseApiClient`) — `REL-CHANGE-001`
- `PublicationRecoveryService` و عملیات log بازیابی — `REL-CHANGE-002`
- تطبیق کاتالوگ WhatsApp و job polling (خودکار غیرفعال پیش‌فرض) — `REL-CHANGE-003`
- فرمان‌ها: `omnichannel:doctor`, `omnichannel:health`, `omnichannel:prune-artifacts` — `REL-CHANGE-004`
- مستندات فاز ۰۲ و pipeline CI — `REL-CHANGE-007`

### تغییرکرده (Changed)

- موردی در CHANGELOG انگلیسی قبلی جداگانه ثبت نشده.

### اصلاح‌شده (Fixed)

- مسیرهای update/delete/deactivate از connection صحیح در scope محدودیت نرخ استفاده می‌کنند — `REL-CHANGE-005`
- closure قفل rate limiter context صحیح را capture می‌کند — `REL-CHANGE-006`

### تغییرات ناسازگار (Breaking Changes)

- موردی در CHANGELOG اعلام نشده؛ تغییرات رفتاری حساس واتس‌اپ و قیود DB در `docs/hardening/UPGRADE-GUIDE.md`.

### مهاجرت و اقدام مصرف‌کننده

- migrationهای `2026_11_20_*`؛ توقف worker و پشتیبان — `hardening/UPGRADE-GUIDE.md`

### محدودیت‌های شناخته‌شده

- تأیید DB فقط SQLite در این نسخه (متن CHANGELOG قبلی)
- اعتبارسنجی کانال زنده و میزبان کامل معلق

### وضعیت اعتبارسنجی

- OK (79 tests, 8911 assertions) — `docs/phase-02/TEST-REPORT.md`

### منابع

- [docs/releases/13.2.0.md](docs/releases/13.2.0.md)

---

## [13.1.0] — نامشخص

- **وضعیت:** نسخه تاریخی گزارش‌شده — **تاریخچه ناقص**
- **خلاصه:** خط پایه شروع فاز ۰۲ (قبل از تکمیل 13.2).
- **راهنما:** [docs/releases/13.1.0.md](docs/releases/13.1.0.md)

### افزوده‌شده / تغییر / اصلاح

- جزئیات در CHANGELOG ریشه موجود نیست؛ ارجاع به `docs/phase-02/BASELINE-AUDIT.md` و `REL-CHANGE-021`.

---

## [13.0.0] — نامشخص

- **وضعیت:** نسخه در `docs/hardening/AUDIT.md` — **تاریخچه ناقص**
- **خلاصه:** ممیزی و مقاوم‌سازی اولیه (STEP 00–08).
- **راهنما:** [docs/releases/13.0.0.md](docs/releases/13.0.0.md) — `REL-CHANGE-020`

### افزوده‌شده / تغییر / اصلاح

- فهرست ریز در `docs/hardening/IMPLEMENTATION-CHECKLIST.md`؛ بدون تکرار کامل در این فایل.

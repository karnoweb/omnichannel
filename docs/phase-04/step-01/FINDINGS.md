# FINDINGS — شناسه‌های OMC-AUD-00x

## OMC-AUD-001 {#omc-aud-001}

- **عنوان:** `composer.lock` با `composer.json` هم‌خوان نیست
- **حوزه:** وابستگی‌ها / انتشار
- **شدت:** متوسط | **اولویت:** P2
- **اطمینان:** بالا
- **نوع:** ریسک بالقوه / شکاف مستندات
- **رفتار فعلی:** `composer validate --no-check-publish` هشدار lock قدیمی می‌دهد.
- **انتظار:** lock با هر تغییر نسخه ۱۳.۳.۰ به‌روز شود.
- **شواهد:** خروجی `composer validate` در [WORKLOG.md](WORKLOG.md)
- **بازتولید:** تأییدشده در ممیزی
- **پیشنهاد:** `composer update` در شاخه مجزا و commit lock (خارج از ممیزی).

## OMC-AUD-002 {#omc-aud-002}

- **عنوان:** ترکیب Illuminate 13 و Testbench 11 بدون سند سازگاری میزبان
- **حوزه:** معماری / پلتفرم
- **شدت:** متوسط | **اولویت:** P1
- **اطمینان:** بالا
- **نوع:** پرسش باز
- **رفتار فعلی:** `require` روی `illuminate/* ^13.0`؛ dev روی `orchestra/testbench ^11.0`.
- **انتظار:** سند یا lock اثبات کند میزبان Karno روی Illuminate 13 است.
- **شواهد:** `composer.json` خطوط 21–33
- **بازتولید:** تحلیل ایستا
- **پیشنهاد:** در README/UPGRADING الزامات میزبان را شفاف کنید.

## OMC-AUD-003 {#omc-aud-003}

- **عنوان:** `composer audit || true` در CI
- **حوزه:** بهره‌برداری
- **شدت:** پایین | **اولویت:** P2
- **نوع:** شکاف مستندات / ریسک
- **رفتار فعلی:** شکست audit خط لوله را نمی‌شکند.
- **شواهد:** `.gitlab-ci.yml` خط ۱۸
- **پیشنهاد:** حداقل گزارش artefact یا job مجزا بدون `|| true`.

## OMC-AUD-004 {#omc-aud-004}

- **عنوان:** نبود Git در مخزن پکیج
- **حوزه:** ممیزی / انتشار
- **شدت:** متوسط | **اولویت:** P2
- **نوع:** محدودیت ممیزی
- **رفتار فعلی:** `git status` ناموفق.
- **اثر:** اثبات عدم تغییر و تاریخچه نسخه سخت‌تر است.
- **شواهد:** [WORKLOG.md](WORKLOG.md)

## OMC-AUD-005 {#omc-aud-005}

- **عنوان:** گزارش فاز ۳ نازک در برابر CHECKLIST کامل
- **حوزه:** مستندات
- **شدت:** متوسط | **اولویت:** P2
- **نوع:** شکاف مستندات
- **رفتار فعلی:** `docs/phase-03/CHECKLIST.md` همه STEPها را [x] زده؛ `FINAL-REPORT.md` ~۶ خط و بدون ماتریس Verified/Pending.
- **انتظار (faz-3):** گزارش DoD با Pending میزبان/Live.
- **شواهد:** `docs/phase-03/FINAL-REPORT.md`, `roadmap/faz-3.md`
- **پیشنهاد:** تکمیل FINAL-REPORT یا ارجاع صریح به این ممیزی.

## OMC-AUD-006 {#omc-aud-006}

- **عنوان:** `PublicationSourceType::Reference` بدون resolver پیش‌فرض
- **حوزه:** منابع انتشار
- **شدت:** پایین (طراحی MVP) | **اولویت:** P3
- **نوع:** شکاف قابلیت جزئی
- **رفتار فعلی:** `PublicationSourceRegistry` خالی؛ ایجاد Reference → RuntimeException.
- **شواهد:** `PublicationCreationService::assertSourceTypeRules`, تست `PublicationSourceValidationTest`
- **تست:** موجود و اجراشده

## OMC-AUD-007 {#omc-aud-007}

- **عنوان:** نبود تست اختصاصی ارتقا ۱۳.۲→۱۳.۳
- **حوزه:** پایگاه داده
- **شدت:** متوسط | **اولویت:** P1
- **نوع:** شکاف تست
- **رفتار فعلی:** `UpgradeFrom130Test` فقط migrationهای تاریخچه فاز ۲ را می‌زند.
- **انتظار (faz-3 STEP 08):** مسیر ارتقا با `2026_11_21_000001`–`000005`.
- **شواهد:** `tests/Feature/UpgradeFrom130Test.php`, migrations squashed

## OMC-AUD-008 {#omc-aud-008}

- **عنوان:** گروه تست `live` برای taxonomy/sync در roadmap بدون پیاده‌سازی
- **حوزه:** تست
- **شدت:** پایین | **اولویت:** P2
- **نوع:** اختلاف roadmap با کد
- **رفتار فعلی:** فقط `@group redis` در Integration.
- **شواهد:** جستجوی `@group live` در `tests/` — صفر نتیجه

## OMC-AUD-009 {#omc-aud-009}

- **عنوان:** MySQL/PostgreSQL در CI و ممیزی اجرا نشده
- **حوزه:** پایگاه داده
- **شدت:** متوسط | **اولویت:** P1
- **نوع:** تأییدنشده
- **رفتار فعلی:** CI فقط SQLite؛ تست‌های Feature با Testbench SQLite.
- **شواهد:** `.gitlab-ci.yml`, [TEST-REPORT.md](TEST-REPORT.md)

## OMC-AUD-010 {#omc-aud-010}

- **عنوان:** Catalog sync پیش‌فرض خاموش — نیاز اتصال میزبان
- **حوزه:** همگام‌سازی کاتالوگ
- **شدت:** پایین (by design) | **اولویت:** P3
- **نوع:** محدودیت محصول
- **رفتار فعلی:** `catalog_sync.enabled` default `false`; تست `CatalogSyncDisabledTest`.
- **شواهد:** `config/omnichannel.php` ~۱۶۵، تست‌های CatalogSync*

## OMC-AUD-011 {#omc-aud-011}

- **عنوان:** اعتبار رسمی endpointهای سرویس‌دهنده تأیید نشده
- **حوزه:** درایورها
- **شدت:** متوسط | **اولویت:** P2 (برای ۴.۲)
- **نوع:** پرسش باز
- **رفتار فعلی:** URLها از `config/omnichannel.php` و کلاینت‌ها استخراج می‌شوند.
- **شواهد:** [DRIVER-AUD.md](DRIVER-AUD.md)

## OMC-AUD-012 {#omc-aud-012}

- **عنوان:** یکپارچگی UI میزبان خارج از پکیج — Pending
- **حوزه:** میزبان
- **شدت:** بالا (عملیاتی) | **اولویت:** P1
- **نوع:** خارج از دامنه پکیج
- **رفتار فعلی:** فقط `HOST-INTEGRATION-PHASE-03.md` و نمونه‌های `docs/examples/host/`.
- **شواهد:** عدم سورس karno-base در workspace
# نقشه راه — karnoweb/omnichannel

| شناسنامه | |
|-----------|---|
| نسخه سورس مبنا | `13.3.0` |
| نسخه سند نقشه راه | 0.1-draft |
| تاریخ | 2026-11-21 |
| وضعیت | **پیش‌نویس** — مصوب نشده |
| مسئول نگهداری | maintainer پکیج (پس از تصویب) |
| ضمائم | [docs/phase-04/step-07/](docs/phase-04/step-07/README.md) |

مرجع محصول: [PRD.md](PRD.md) (0.1-draft). معماری: [ARCHITECTURE.md](ARCHITECTURE.md). انتشار: [CHANGELOG.md](CHANGELOG.md)، [UPGRADING.md](UPGRADING.md).

---

## ۱. خلاصه اجرایی

**اکنون:** پکیج Laravel چندکاناله بدون UI؛ ۹ درایور؛ اسکلت فاز ۳ (sources، batch، media، taxonomy، catalog sync خاموش)؛ ۱۰۲ تست SQLite ثبت‌شده؛ MySQL/PG/Redis/live/میزبان **تأییدنشده**. فاز ۴ مستند: ۴.۱–۴.۶ انجام؛ **۴.۷** این سند؛ **۴.۸** بعد.

**مقصد نسخه پایدار:** دامنه PRD مصوب، P0/P1 بسته یا ریسک پذیرفته‌شده، DOC-001..004، live حداقلی کانال‌های سطح A (تعریف در PROVIDER-SUPPORT-POLICY — هنوز مصوب نشده). نه پوشش کامل API همه سرویس‌ها.

**موانع اصلی:** DEC-001..008 باز؛ تست ارتقا و DB prod (P43-GAP-007/008)؛ شواهد Rubika/Gap/Eitaa؛ tag/Packagist (HIST-02).

**قدم بعدی پیشنهادی:** تکمیل **۴.۸ CONTRIBUTING** → تصویب PRD/DECهای مسدودکننده → **WP-05-01** (ارتقا 13.3) — فقط پس از مجوز پیاده‌سازی، نه در ۴.۷.

---

## ۲. دامنه نسخه پایدار و غیرهدف

**شامل (هدف):** انتشار/زمان‌بندی، batch، رسانه، درایورهای مصوب، taxonomy/sync opt-in، مستندات فارسی، تاریخچه نسخه.

**خارج:** UI میزبان، webhook (فعلاً)، inbox، پوشش اجباری همه قابلیت‌های رسمی کانال‌های C. جزئیات: [DEFERRED-AND-OUT-OF-SCOPE.md](docs/phase-04/step-07/DEFERRED-AND-OUT-OF-SCOPE.md).

**سطح پشتیبانی:** جدول فعلی/هدف → [PROVIDER-ROADMAP.md](docs/phase-04/step-07/PROVIDER-ROADMAP.md).

**اصل ثابت:** `catalog_sync.enabled` پیش‌فرض false؛ مرجع محصول در میزبان.

---

## ۳. وضعیت گذشته و حال

| فاز تاریخی | خلاصه |
|------------|--------|
| hardening → 13.0 | مقاوم‌سازی انتشار |
| فاز ۲ → 13.2 | rate limit، recovery، WA reconcile |
| فاز ۳ → 13.3 | sources، media، batch، taxonomy، catalog foundation |
| فاز ۴ | ممیزی، providers، GAP، PRD، ARCHITECTURE، CHANGELOG |

کارهای ناقص منتقل‌شده: P43-GAP-001..015، OMC-AUD-001..012 → [WORK-PACKAGES.md](docs/phase-04/step-07/WORK-PACKAGES.md).

---

## ۴. نمای نقشه راه (فاز ۵–۹)

| فاز | هدف | بسته‌های شاخص | ورود | خروج |
|-----|------|----------------|------|------|
| ۵ | درایور + هسته | WP-05-01..08 | ۴.۸ | G5 |
| ۶ | کاتالوگ | WP-06-xx | DEC-003 | G6 |
| ۷ | میزبان + DX | WP-07-xx | قرارداد پایدار | G7 |
| ۸ | اعتبارسنجی | WP-08-xx | DB/live آماده | G8 |
| ۹ | انتشار پایدار | WP-09-xx | G8 | تصمیم مالک |

جزئیات: [PHASE-PLAN.md](docs/phase-04/step-07/PHASE-PLAN.md). وابستگی: [DEPENDENCY-MAP.md](docs/phase-04/step-07/DEPENDENCY-MAP.md).

---

## ۵. چهار الزام مالک

| الزام | اجرا در نقشه راه |
|--------|-------------------|
| CHANGELOG/ارتقا هر نسخه | WP-DOC-001 + RELEASE-AND-MIGRATION |
| مستند هر سرویس | WP-DOC-002 + PROVIDER-ROADMAP |
| فارسی فنی | WP-DOC-003 + دروازه G7/G8 |
| نقشه راه زنده | WP-DOC-004 + روش پایش §۱۱ |

---

## ۶. مسیر آزمون و پایدارسازی

- محلی: PHPUnit `--exclude-group redis` (حداقل مستمر)
- موتور DB: WP-08-01/02 (NFR-010)
- Redis: WP-05-06
- امنیت شعب: G8 / OMC-AUD-002
- Live: WP-08-005 جدا از mock

معیارها: [QUALITY-GATES.md](docs/phase-04/step-07/QUALITY-GATES.md).

---

## ۷. انتشار و مهاجرت

نقاط تحویل، بدون شماره نسخه قطعی: [RELEASE-AND-MIGRATION-PLAN.md](docs/phase-04/step-07/RELEASE-AND-MIGRATION-PLAN.md). سیاست: [docs/releases/RELEASE-POLICY.md](docs/releases/RELEASE-POLICY.md).

---

## ۸. ریسک، تعویق، خارج از دامنه

[RISK-AND-BLOCKER-REGISTER.md](docs/phase-04/step-07/RISK-AND-BLOCKER-REGISTER.md) — [DEFERRED-AND-OUT-OF-SCOPE.md](docs/phase-04/step-07/DEFERRED-AND-OUT-OF-SCOPE.md).

---

## ۹. معیار پایان و Production Verified

- عبور دروازه‌های فاز ۸ برای حوزه‌های ادعا شده
- تکمیل RELEASE-CHECKLIST با شواهد
- تصریح محدودیت هر کانال/محیط در مستند نسخه
- **102 تست SQLite کافی نیست** به‌تنهایی برای تولید

تصمیم نهایی: نقش مالک/مسئول انتشار — خارج از این سند.

---

## ۱۰. پایش پیشرفت

سه سطح: تحویل بسته (WP) / پذیرش فاز (G*) / آمادگی پایدار (WP-09-03).

به‌روزرسانی: تغییر دامنه → نسخه سند + WP-DOC-004 + ردیف [ROADMAP-TRACEABILITY.md](docs/phase-04/step-07/ROADMAP-TRACEABILITY.md). بدون درصد ساختگی.

ردیابی کامل: [ROADMAP-TRACEABILITY.md](docs/phase-04/step-07/ROADMAP-TRACEABILITY.md).

---

## ۱۱. فهرست کار

۳۵ بسته — [WORK-PACKAGES.md](docs/phase-04/step-07/WORK-PACKAGES.md). اولویت: [PRIORITIZATION-AND-ESTIMATES.md](docs/phase-04/step-07/PRIORITIZATION-AND-ESTIMATES.md).

---

## ۱۲. مرحله ۴.۸ و شروع پیاده‌سازی

ورودی: [NEXT-STEPS.md](docs/phase-04/step-07/NEXT-STEPS.md).

شرط شروع فاز ۵ پیاده‌سازی: پذیرش چک‌لیست ۴.۷ + ۴.۸؛ تصویب PRD؛ بستن یا پذیرش ریسک DECهای مسدودکننده IG/Graph؛ **بدون** این موارد کار کد می‌تواند در محدوده WP-05-01 و مستندات ادامه یابد فقط پس از تصویب صریح مالک.

تصمیم‌های باز: [DECISIONS.md](docs/phase-04/step-07/DECISIONS.md).
## پیگیری فاز ۵.۹

تصمیم‌های مالک و کنترل‌های محلی فاز ۵.۹ تکمیل شدند. تأیید اجرای گیت‌لب برای ورود به فاز ۶ باقی است. [مصوبهٔ مالک](docs/phase-05/step-09/DECISIONS-AND-BLOCKERS.md)

## فاز ۶ — کاتالوگ

همگام‌سازی قیمت/موجودی، taxonomy mock باسلام، همگرایی batch واتس‌اپ. پذیرش مشروط: live taxonomy → فاز ۸.۵. [PHASE-CLOSURE](docs/phase-06/PHASE-CLOSURE.md)

## فاز ۷ — میزبان و DX

راهنمای یکپارچگی، API فارسی، registrar Reference، درایور سفارشی. [PHASE-CLOSURE](docs/phase-07/PHASE-CLOSURE.md)

## فاز ۸ — اعتبارسنجی عملیاتی

CI MySQL، امنیت/صف، batch. live و PG مشروط. [PHASE-CLOSURE](docs/phase-08/PHASE-CLOSURE.md)

## فاز ۹ — انتشار

نسخه **13.4.0**، GitHub Actions، RELEASE-CHECKLIST. بدون Production Verified. [PHASE-CLOSURE](docs/phase-09/PHASE-CLOSURE.md)

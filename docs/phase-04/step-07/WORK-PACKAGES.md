# WORK-PACKAGES — بسته‌های کار

نسخه مبنا: `13.3.0`. وضعیت همه بسته‌ها: **برنامه‌ریزی‌شده** (بدون پیاده‌سازی در ۴.۷).

## الزام‌های مالک (نگهداری مداوم)

| شناسه | نوع | منشأ | هدف | اولویت | فاز |
|--------|-----|------|-----|--------|-----|
| WP-DOC-001 | مستند | DOC-001 | هر تغییر رفتاری → `CHANGELOG.md` + `UPGRADING.md` | P0 | همه |
| WP-DOC-002 | مستند | DOC-002 | به‌روزرسانی `docs/providers/` و تاریخ اعتبارسنجی | P0 | ۵+ |
| WP-DOC-003 | مستند | DOC-003 | بازبینی فارسی فنی هر بسته قبل از پذیرش | P0 | همه |
| WP-DOC-004 | مستند | DOC-004 | به‌روزرسانی `ROADMAP.md` هنگام تغییر دامنه/اولویت | P1 | همه |

## فاز ۵ — درایورها و زیرساخت

| شناسه | عنوان | منشأ | P | برآورد | معیار پذیرش (خلاصه) |
|--------|--------|------|---|--------|---------------------|
| WP-05-01 | گسترش `UpgradeFrom130Test` / FR-038 | P43-GAP-007, OMC-AUD-007, FR-038 | P0 | M | migrations `2026_11_21_*` در سناریوی ارتقا پوشش داده شده؛ تست سبز |
| WP-05-02 | سیاست نرخ Instagram | P43-GAP-002, DEC-001, FR-031 | P1 | M | پس از DEC-001: config/doc هم‌راستا Meta؛ تست rate |
| WP-05-03 | Graph API version bump | P43-GAP-003, DEC-006 | P1 | M | نسخه هدف مصوب؛ regression IG؛ DOC-002 |
| WP-05-04 | جداسازی/هم‌ترازی Bale client | P43-GAP-005 | P2 | S | پارامترهای TG/Bale بدون رگرسیون |
| WP-05-05 | واتس‌اپ UnknownOutcome و batch | REL-CHANGE-003, hardening | P1 | M | رفتار مستند = تست؛ poller/inspect هم‌راستا PRD |
| WP-05-06 | Redis rate limit integration | OMC-AUD-009, NFR | P1 | M | `@group redis` یا CI اجرا شود؛ شاهد ثبت |
| WP-05-07 | قرارداد `capabilities()` رگرسیون | step-03 matrix | P2 | M | هر درایور: عملیات اعلام‌شده با تست قرارداد |
| WP-05-08 | Rubika/Gap شواهد رسمی | P43-GAP-010/011, DEC-005 | P1 | L | doc رسمی یا تصمیم best-effort مستند در PROVIDER |

## فاز ۶ — کاتالوگ و بازارگاه

| شناسه | عنوان | منشأ | P | برآورد | معیار پذیرش |
|--------|--------|------|---|--------|-------------|
| WP-06-01 | Basalam status mapping | P43-GAP-004 | P1 | M | deactivate/حذف با شناسه تأییدشده؛ تست + providers/basalam |
| WP-06-02 | catalog_sync opt-in | DEC-003, FR-026, OMC-AUD-010 | P1 | M | پیش‌فرض false؛ فعال‌سازی فقط پس از registrar میزبان |
| WP-06-03 | taxonomy live validation | P43-GAP-009, OMC-AUD-008 | P2 | L | plan `@group live` یا staging؛ نتیجه جدا از mock |
| WP-06-04 | WhatsApp catalog reconciliation | P43-DEC-004, FR-042 | P2 | M | هم‌راستایی با DEC-004 catalog-only |

## فاز ۷ — توسعه‌پذیری و میزبان

| شناسه | عنوان | منشأ | P | برآورد | معیار پذیرش |
|--------|--------|------|---|--------|-------------|
| WP-07-01 | Reference source registrar | P43-GAP-013, OMC-AUD-006, FR-039 | P3 | M | بدون registrar → exception روشن؛ مثال میزبان |
| WP-07-02 | راهنمای یکپارچگی میزبان | P43-GAP-014, OMC-AUD-012 | P1 | M | تکالیف میزبان جدا؛ checklist HOST-INTEGRATION |
| WP-07-03 | مستند API عمومی فارسی | DOC-003, ARCHITECTURE | P1 | L | قراردادهای public در یک نمایه با مثال |
| WP-07-04 | سازگاری Illuminate 13 میزبان | P43-GAP-006, DEC-007, NFR-014 | P1 | S | سند الزام نسخه میزبان + لینک UPGRADING |
| WP-07-05 | درایور سفارشی — راهنمای توسعه | step-05 DRIVER-ARCHITECTURE | P2 | M | فرآیند ثبت درایور بدون وابستگی karno-base |

## فاز ۸ — اعتبارسنجی عملیاتی

| شناسه | عنوان | منشأ | P | برآورد | معیار پذیرش |
|--------|--------|------|---|--------|-------------|
| WP-08-01 | CI MySQL/PG | P43-GAP-008, NFR-010 | P1 | M | pipeline اجرای migrate/تست روی موتور دوم |
| WP-08-02 | مهاجرت MySQL/PG دستی | OMC-AUD-009, step-02 DB | P0 | L | migrate ارتقا بدون شکست؛ گزارش محیط |
| WP-08-03 | امنیت: شعب و اسرار | OMC-AUD-002, FINDINGS | P0 | M | سناریوهای تست امنیتی PASS در نامزد انتشار |
| WP-08-04 | صف چند worker | VERIFICATION-STATUS | P1 | L | قفل/idempotency تحت بار موازی |
| WP-08-05 | live حداقلی کانال A | PROVIDER-SUPPORT-POLICY | P1 | L | پس از مجوز: IG/TG/BS حداقل مسیر publish در staging |
| WP-08-06 | تست کارایی انتشار گروهی | FR batch | P2 | M | یک مقصد fail، دیگر محفوظ — معیار پرامپت §۸ |

## فاز ۹ — نسخه پایدار

| شناسه | عنوان | منشأ | P | برآورد | معیار پذیرش |
|--------|--------|------|---|--------|-------------|
| WP-09-01 | اجرای RELEASE-CHECKLIST | docs/releases | P0 | S | چک‌لیست ۴.۶ + ۴.۸ کامل با شواهد |
| WP-09-02 | tag و انتشار | HIST-01/02, REL-DEC-002 | P0 | S | تصمیم مالک؛ version/tag/CHANGELOG هم‌خوان |
| WP-09-03 | Production Verified اعلام | QUALITY-GATES فاز ۹ | P0 | — | فقط پس از عبور دروازه‌ها — نه با 102 تست SQLite تنها |
| WP-09-04 | P43-GAP-015 مستندات فاز ۳ | OMC-AUD-005 | P2 | S | HOST-INTEGRATION و phase-03 تکمیل یا ارجاع |

## تصمیم / تحقیق (بدون کد تا تصویب)

| شناسه | عنوان | منشأ | وضعیت |
|--------|--------|------|--------|
| WP-DEC-002 | Eitaa سطح پشتیبانی | DEC-002, P43-GAP-012 | مسدود محصولی |
| WP-DEC-008 | Webhook دامنه | DEC-008, FR-040 | تعویق |
| WP-DEC-005 | Rubika/Gap سطح | DEC-005 | باز |

## قالب تکمیلی (نمونه WP-05-01)

| فیلد | مقدار |
|------|--------|
| نقش مسئول | توسعه‌دهنده پکیج |
| پذیرنده | maintainer + DevOps برای CI |
| پیش‌نیاز | DEC تصمیم‌های مسدودکننده مهاجرت — ندارد |
| خروجی آینده | `tests/Upgrade/UpgradeFrom130Test.php`, CHANGELOG |
| اثر ارتقا | مصرف‌کننده 13.2→13.3؛ UPGRADING به‌روز |
| مانع | — |

تعداد یکتا: **۳۵** بسته (۴ DOC + ۲۲ پیاده‌سازی/آزمون + ۳ تصمیم + ۶ فاز ۹/۸ ترکیبی). بسته‌های ادغام‌شده با شناسه منبع در [ROADMAP-TRACEABILITY.md](ROADMAP-TRACEABILITY.md).
# BASELINE-AND-INPUTS

## نسخه و مخزن

| مورد | مقدار |
|------|--------|
| `composer.json` version | `13.3.0` |
| PRD | 0.1-draft — **مصوب نشده** |
| ARCHITECTURE | To-Be مشروط DEC |
| Git tag | ندارد (step-06 HISTORY-GAPS) |

## وضعیت مراحل فاز ۴

| مرحله | خروجی | وضعیت مستند |
|--------|--------|----------------|
| ۴.۱ | `docs/phase-04/step-01/` | تهیه شده |
| ۴.۲ | `docs/providers/` | تهیه شده |
| ۴.۳ | `docs/phase-04/step-03/` | تهیه شده |
| ۴.۴ | `PRD.md` + step-04 | تهیه شده (draft) |
| ۴.۵ | `ARCHITECTURE.md` + step-05 | تهیه شده (uncommitted) |
| ۴.۶ | CHANGELOG/UPGRADING + step-06 | تهیه شده (uncommitted) |
| **۴.۷** | ROADMAP + step-07 | **این مرحله** |
| ۴.۸ | CONTRIBUTING | بعدی |

شش مرحله از هشت مرحله فاز چهارم **مستند تحویل‌شده** دارند؛ این شمار مرحله است، نه درصد آمادگی محصول.

## شواهد آزمون مبنا

| محیط | شاهد | وضعیت |
|------|------|--------|
| SQLite PHPUnit | 102 tests — `step-01/TEST-REPORT` | تاریخی/۴.۱ |
| Redis group | حذف از پیش‌فرض | بدون اجرا در ۴.۷ |
| MySQL/PG migrate | — | نامشخص |
| میزبان کامل | OMC-AUD-012 | خارج دامنه پکیج |
| Live API | — | نامشخص |

## ورودی‌های کلیدی از ۴.۶

- REL-DEC-004 ارتقای چندپله تأیید نشده
- OMC-AUD-007 / FR-038 مسیر ارتقا 13.3
- DEC-001..008 باز — [`step-04/NEXT-STEPS`](../step-04/NEXT-STEPS.md)

## تعارض‌های برنامه‌ریزی (بدون بازنویسی اسناد قدیمی)

| موضوع | A | B | ثبت در ۴.۷ |
|--------|---|---|------------|
| مقصد محصول | PRD draft | ARCHITECTURE To-Be | مشروط تصویب مالک |
| اولویت | P43 PRIORITIZATION | پرامپت P0–P3 | نگاشت در PRIORITIZATION-AND-ESTIMATES |
| Production Verified | 13.3 در composer | VERIFICATION-STATUS | **خیر** تا فاز ۸–۹ |

## کمبود داده

- تاریخ انتشار Packagist/tag
- لیست کانال‌های سطح A مصوب برای live
- ظرفیت و تقویم تیم — **ثبت نشده**
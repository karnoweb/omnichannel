# RELEASE-AND-MIGRATION-PLAN

مرجع: [CHANGELOG](../../CHANGELOG.md)، [UPGRADING](../../UPGRADING.md)، [RELEASE-POLICY](../../docs/releases/RELEASE-POLICY.md)، [MIGRATION-REVIEW](../step-06/MIGRATION-REVIEW.md).

## نقاط تحویل (آینده)

| نقطه | معنی | شماره نسخه |
|------|------|------------|
| داخلی | merge به main بدون tag | — |
| آزمایشی | tag pre-release در صورت سیاست | **باز** |
| نامزد | عبور فاز ۸ | **باز** |
| پایدار | WP-09-03 | **باز** |

یک فاز می‌تواند چند patch یا یک minor را شامل شود.

## مسیر مهاجرت مصرف‌کننده

- نصب تازه: squashed یک‌جا — بدون تغییر نسبت به ۴.۶
- 13.2→13.3: WP-05-01 قبل از ادعای آمادگی
- چندپله 13.0/13.1: **تأیید نشده** — مشروط REL-DEC-004

## فعال‌سازی قابلیت

- `catalog_sync.enabled`: فقط پس از WP-06-02 + میزبان
- live channels: پس از WP-08-05

## بازگشت

- همان محدودیت‌های UPGRADING — rollback داده جدا از کد
- عملیات API خارجی غیرقابل برگشت محلی

## تغییر ناسازگار

- فقط با PRD مصوب + MINOR/MAJOR در RELEASE-POLICY + UPGRADING

## شرط توقف انتشار

- نقص G8 امنیت/داده باز
- DEC مسدودکننده بدون تصمیم
- بدون tag شواهد (HIST-02) اگر انتشار عمومی هدف باشد
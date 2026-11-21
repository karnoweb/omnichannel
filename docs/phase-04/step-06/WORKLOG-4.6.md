# WORKLOG — مرحله ۴.۶ Changelog

| فیلد | مقدار |
|------|--------|
| تاریخ بررسی | 2026-11-21 (Asia/Tehran) |
| پکیج | `karnoweb/omnichannel` |
| نسخه اعلام‌شده | `13.3.0` (`composer.json`) |
| نوع کار | مستندسازی تاریخچه و ارتقا — بدون تغییر کد |

## وضعیت Git (فقط‌خواندنی)

| بررسی | نتیجه |
|--------|--------|
| `git log` | یک commit: `2e6593a` — `docs: phase 4 audit, capability matrix, and PRD (13.3.0)` |
| `git tag --list` | خالی — tag نسخه در مخزن محلی ثبت نشده |
| فایل‌های untracked نسبت به commit | `ARCHITECTURE.md`, `docs/phase-04/step-05/`, پرامپت‌های roadmap ۴.۵ و ۴.۶ |
| محدودیت | تاریخچه Git کافی برای انتساب تغییر به commit نیست؛ اتکا به مستندات فاز ۰۲/۰۳ و `CHANGELOG.md` |

## منابع استفاده‌شده

- `CHANGELOG.md` (انگلیسی، نسخه‌های 13.2.0 و 13.3.0)
- `docs/phase-02/`، `docs/phase-03/`، `docs/hardening/`
- `docs/phase-04/step-01`..`05`، `PRD.md`، `ARCHITECTURE.md`
- `database/migrations_squashed/` (خواندن برای مستند مهاجرت)
- **PHPUnit در این مرحله اجرا نشد** — آمار از گزارش‌های تاریخی

## خروجی‌های این مرحله

ریشه: `CHANGELOG.md` (فارسی)، `UPGRADING.md`  
`docs/releases/*`، `docs/phase-04/step-06/*`
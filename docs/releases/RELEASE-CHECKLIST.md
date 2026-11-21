# چک‌لیست پذیرش و انتشار

پیش‌نویس ۴.۶ — مسئول پیشنهادی: maintainer پکیج + تأیید مالک برای breaking.

| # | مورد | وضعیت | شاهد | شرط پذیرش |
|---|------|--------|------|-----------|
| 1 | طبقه‌بندی تغییرات (patch/minor/major) | — | CHANGELOG Unreleased | تأیید مالک برای major |
| 2 | تکمیل CHANGELOG فارسی | — | [CHANGELOG.md](../../CHANGELOG.md) | هر تغییر رفتاری |
| 3 | به‌روزرسانی UPGRADING در صورت نیاز | — | [UPGRADING.md](../../UPGRADING.md) | migration/config breaking |
| 4 | مستند فارسی درایورهای متاثر | — | `docs/providers/` | تغییر رفتار کانال |
| 5 | `docs/releases/<version>.md` | — | پوشه releases | هر انتشار |
| 6 | تطابق ماتریس قابلیت با کد | — | step-03 | ادعای قابلیت جدید |
| 7 | PHPUnit `--exclude-group redis` | — | خروجی ترمینال | سبز در CI محلی |
| 8 | تست ارتقا از نسخه قبل | — | Upgrade* tests | اگر migration/config |
| 9 | MySQL/PG migrate (در صورت ادعا) | — | لاگ migrate | فقط اگر در VERIFICATION اعلام شود |
| 10 | Redis / چند worker | — | Redis group tests | فقط اگر rate limit تولیدی |
| 11 | اعتبارسنجی میزبان | — | host suite | در صورت نیاز محصول |
| 12 | API زنده | — | محیط staging | اختیاری؛ مجوز |
| 13 | هم‌خوانی version / tag / CHANGELOG | — | git tag, composer.json | قبل از push |
| 14 | rollback plan | — | MIGRATION-REVIEW | قبل از prod migrate |
| 15 | مشکلات شناخته‌شده در CHANGELOG | — | Known Limitations | صادقانه |
| 16 | ثبت تاریخ انتشار واقعی پس از وقوع | — | GitHub/Packagist | نه پیشاپیش |

**تغییر فایل CI در مرحله ۴.۶ انجام نشده است.**
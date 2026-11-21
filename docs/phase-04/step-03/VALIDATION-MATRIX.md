# VALIDATION-MATRIX

مبنای اولیه: [step-01/TEST-REPORT.md](../step-01/TEST-REPORT.md) | به‌روز تست: [WORKLOG.md](WORKLOG.md)

## اجرای محلی (این مرحله)

| محیط | فرمان | نتیجه | شواهد |
|---|---|---|---|
| SQLite/Testbench | `phpunit --exclude-group redis` | **102 OK** | P43-EV-001 |
| Redis group | — | اجرا نشده | مانع محیط |
| MySQL/PG | — | اجرا نشده | OMC-AUD-009 |

## پوشش سناریو به تفکیک خانواده

| خانواده | تست محلی | تست عملی خارجی |
|---|---|---|
| درایور Contract | موفق همه درایورها | اجرا نشده |
| انتشار / batch | Feature tests موفق | اجرا نشده |
| رسانه / SSRF | HttpMedia* موفق | اجرا نشده |
| Catalog sync off/on | CatalogSync* موفق | اجرا نشده |
| WhatsApp reconcile | mock موفق | اجرا نشده |
| Taxonomy sync زنده | ندارد / live plan | مسدود P42-Q-002 |

## شکاف اعتبارسنجی ↔ شکاف

| P43-GAP | نیاز آینده |
|---|---|
| P43-GAP-007 | تست migration 13.3 |
| P43-GAP-008 | job CI MySQL |
| P43-GAP-009 | `@group live` taxonomy |
| P43-GAP-010–012 | حساب/توکن + live validation plan |

## آمادگی عملیاتی (خلاصه)

| سرویس | محلی | تولید |
|---|---|---|
| Instagram/Telegram/Basalam | شواهد قوی mock | نیاز live |
| WhatsApp catalog | mock batch | نیاز Commerce حساب |
| Bale | mock | نیاز bot |
| Rubika/Gap/Eitaa | mock contract | شواهد رسمی ناکافی |
| Demo | کامل شبیه‌سازی | نامرتبط |

برنامه live بدون اجرا: [LIVE-VALIDATION-PLAN](../step-02/LIVE-VALIDATION-PLAN.md).
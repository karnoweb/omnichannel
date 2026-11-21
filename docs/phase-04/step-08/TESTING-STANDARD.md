# TESTING-STANDARD

## انواع آزمون

| نوع | هدف | مسیر نمونه |
|-----|------|------------|
| Unit | منطق خالص | `tests/Unit/` |
| Feature | سرویس و جریان | `tests/Feature/` |
| Architecture | مرز میزبان | `NoHostDependencyTest`, `PackageIsolationTest` |
| Integration | DB/Redis | `tests/Integration/` |
| Driver | قرارداد کانال | `tests/Drivers/` |
| Migration/Upgrade | نصب/ارتقا | `UpgradeFrom130Test` و مشابه |

## اصول

- مستقل، تکرارپذیر، نام روشن
- بدون شبکه در suite پیش‌فرض (mock HTTP)
- داده ساختگی
- شکست = برنامه‌ریزی اصلاح، نه skip بی‌دلیل

## ثبت شواهد

هر اجرا: commit/نسخه، PHP، فرمان دقیق، exit code، counts، skip با دلیل.

وضعیت‌ها: موفق | ناموفق | اجرا نشده | skip | مسدود.

## گروه‌ها

- `redis`: `RedisRateLimitIntegrationTest` — نیاز Redis
- `live`: در صورت وجود — فقط staging با مجوز

پیش‌فرض توسعه و CI: `--exclude-group redis`.

## رگرسیون

هر باگ P0/P1: تستی که قبل از fix fail و بعد pass شود — یا دلیل عدم امکان.

## اجرا در ۴.۸

PHPUnit مجدد **اجرا نشده** — مرجع: [step-01/TEST-REPORT](../step-01/TEST-REPORT.md) (102 tests, SQLite).
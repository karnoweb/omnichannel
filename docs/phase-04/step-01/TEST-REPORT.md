# TEST-REPORT — مرحله ۴.۱

## محیط اجرای PHPUnit

| مورد | مقدار |
|---|---|
| تاریخ | ۲۰۲۶-۱۱-۲۱ |
| PHP | 8.4.22 |
| PHPUnit | 13.4.7 (از vendor) |
| پایگاه داده تست | SQLite در حافظه (Testbench + migrations squashed) |
| پیکربندی | `phpunit.xml` — یک testsuite، بدون تعریف `<groups>` |

## اجرای انجام‌شده

```text
vendor/bin/phpunit --exclude-group redis
OK (102 tests, 12738 assertions)
Time: ~20–28s, Memory: ~74 MB
```

| نوع شاهد | توضیح |
|---|---|
| تست اجراشده | مجموعه بالا در محیط لوکال Windows |

### گروه `redis`

| مورد | وضعیت |
|---|---|
| تست با `@group redis` | `tests/Integration/RedisRateLimitIntegrationTest.php` |
| اثر `--exclude-group redis` | آن تست از اجرای پیش‌فرض حذف می‌شود |
| اجرای گروه redis در این ممیزی | **انجام نشد** — نیاز به Redis/configure |

## اجرا نشده

| مورد | دلیل |
|---|---|
| MySQL | بدون نمونه ایزوله و phpunit env اختصاصی در CI |
| PostgreSQL | همان |
| تست‌های `@group live` | در سورس **وجود ندارد** (ادعای `roadmap/faz-3.md` — ر. [OMC-AUD-008](FINDINGS.md#omc-aud-008)) |
| Redis integration | عمداً حذف با exclude-group؛ CI هم Redis ندارد |

## CI — `.gitlab-ci.yml`

| مرحله | محتوا |
|---|---|
| image | `php:8.3-cli` |
| DB | `pdo_sqlite` فقط |
| script | `pint --test`, `phpunit --exclude-group redis`, `composer validate --strict`, `composer audit \|\| true` |

**نکته:** `composer audit || true` شکست audit را در خط لوله نادیده می‌گیرد — ر. [OMC-AUD-003](FINDINGS.md#omc-aud-003). در محیط ممیزی، `composer audit` محلی **بدون advisory** بود.

## پوشش کیفی (نگاشت سطحی)

| پوشه تست | تعداد فایل (تقریبی) | دامنه |
|---|---|---|
| `tests/Feature/` | ۳۹ | انتشار، batch، media، catalog policy، rate limit، recovery |
| `tests/Unit/` | ۲ | SecretRedactor، ApiResponse |
| `tests/Architecture/` | ۳ | بدون `App\*`، FK میزبان |
| `tests/Drivers/` | ۱ | قرارداد درایور |
| `tests/Integration/` | ۱ | Redis (گروه redis) |

شکاف‌های مهم: ارتقا **۱۳.۲→۱۳.۳** با migrationهای `2026_11_21_*` — ر. [OMC-AUD-007](FINDINGS.md#omc-aud-007).

## Illuminate / Testbench

از `composer.json`: `illuminate/* ^13.0`, `orchestra/testbench ^11.0`.

`composer validate` هشدار: **lock file is not up to date**. نسخه‌های قفل‌شده Illuminate باید از `composer.lock` خوانده شوند؛ فرض سازگاری خودکار ممنوع — ر. [OMC-AUD-002](FINDINGS.md#omc-aud-002).
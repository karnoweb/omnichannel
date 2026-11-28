# نتایج آزمون فاز ۵.۹

محیط محلی Windows، PHP 8.4.22، Composer 2.10.2، PHPUnit 13.4.7 و SQLite. درخواست سرویس‌دهندگان با پاسخ مصنوعی آزموده شد. اجرای گیت‌لب و محیط عملیاتی مشاهده نشده‌اند.

| فرمان یا گروه | کد خروج | نتیجه |
|---|---:|---|
| `php vendor/bin/phpunit --exclude-group redis`، مبنا | 0 | ۱۱۰ تست و ۱۲۸۶۶ بررسی. تعریف قدیمی گروه Redis با نسخه فعلی PHPUnit سازگار نبود. |
| `php vendor/bin/phpunit tests/Feature/WhatsAppCatalogBatchOutcomeTest.php`، پیش از اصلاح | 1 | ۱۴ تست، ۶ شکست ناشی از موفقیت کاذب |
| `php vendor/bin/phpunit tests/Drivers/DriverContractTest.php`، پیش از اصلاح نتیجه اتصال | 2 | خطای متغیر تعریف‌نشده در مسیر اتصال ناموفق |
| اجرای اختصاصی نخست، مسیرهای درایورها، واتس‌اپ، ارتقا، نرخ، تکرار درخواست و توکن | 0 | ۴۸ تست و ۲۴۱ بررسی |
| اجرای اختصاصی نهایی، tests/Drivers، UnknownOutcomeTest، UpgradeFrom130Test و SharedAccountRateLimitTest | 0 | ۳۲ تست و ۲۴۹ بررسی |
| `php vendor/bin/phpunit --exclude-group redis`، نهایی | 0 | ۱۴۳ تست و ۱۳۵۱۶ بررسی، بدون شکست یا ردشدن در مجموعه انتخاب‌شده |
| `php vendor/bin/phpunit --group redis` | 0 | ۴ تست و ۱۰ بررسی با Redis 8.8.0 واقعی |
| `composer check-platform-reqs --lock` | 0 | حداقل اعلامی PHP 8.4.1 است و پیش‌نیازها روی PHP 8.4.22 برقرارند |
| `php vendor/bin/pint --test --dirty` | 0 | قالب فایل‌های تغییرکرده سازگار است |
| `php vendor/bin/pint --test`، کل مخزن | 0 | پس از اصلاح قالب کل مخزن با مجوز مالک |
| composer validate --strict، پس از همسان‌سازی هش | 1 | هش قدیمی رفع شد. هشدار فیلد version در بسته باقی است. |
| composer validate --strict --no-check-version، همان فرمان گیت‌لب | 0 | فقط هشدار حضور نسخهٔ صریح مستثناست. خطاها و سایر هشدارها همچنان باعث شکست می‌شوند. |
| `composer audit` | 100 | پایان مهلت ارتباط با Packagist |
| `composer audit --locked` | 0 | بررسی نسخه‌های قفل‌شده، بدون گزارش آسیب‌پذیری |
| `git diff --check` | 0 | بدون خطای فاصله‌گذاری |

فرمان مسیرهای اختصاصی شامل tests/Drivers و تست‌های WhatsAppCatalogBatchOutcomeTest، UpgradeFrom130Test، FreshInstallationTest، MigrationRollbackTest، SharedAccountRateLimitTest، DriverRequestCountingTest، RetryAfterPropagationTest، TokenRefreshConcurrencyTest و UnknownOutcomeTest بود. گزارش [Redis](REDIS-EVIDENCE.md) و [ماتریس پوشش](DRIVER-REGRESSION-MATRIX.md) دامنهٔ شواهد را مشخص می‌کنند.

## راستی‌آزمایی ۲۰۲۶-۱۱-۲۸

مبنای Git همان `31b973d` روی `main`؛ تغییرات محلی commit نشده بدون reset/clean. هیچ اصلاح کدی در این دوره انجام نشد.

| فرمان یا گروه | کد خروج | نتیجه |
|---|---:|---|
| `composer check-platform-reqs` | 0 | PHP 8.4.22 برابر `^8.4.1` |
| `composer validate --strict --no-check-version` | 0 | |
| `vendor/bin/pint --test` | 0 | کل مخزن |
| `vendor/bin/phpunit --exclude-group redis` | 0 | ۱۴۴ تست و ۱۳۶۴۲ بررسی |
| دروازه‌های G5-01 / G5-03 / WP-05-05 | 0 | ۳۴ تست در UpgradeFrom130Test، FreshInstallationTest، MigrationRollbackTest، WhatsAppCatalogBatchOutcomeTest و DriverContractTest |
| `vendor/bin/phpunit --group redis` بدون `OMNICHANNEL_TEST_REDIS_HOST` | 0 | ۴ تست skipped؛ معادل PASS نیست |
| `vendor/bin/phpunit --group redis` با `127.0.0.1:6379` و Redis 8.8.0 | 0 | ۴ تست و ۱۰ بررسی |
| `composer audit --locked` | 100 | timeout ارتباط با Packagist؛ BLOCKED نسبت به دروازه CI |

تفاوت شمارش نسبت به اجرای ۲۰۲۶-۱۱-۲۵: یک تست بیشتر در مجموعه بدون redis (۱۴۴ در برابر ۱۴۳).

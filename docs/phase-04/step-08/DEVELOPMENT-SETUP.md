# DEVELOPMENT-SETUP

## پیش‌نیازهای سیستم

| مورد | حداقل شواهد مخزن |
|------|-------------------|
| PHP | 8.3 (`composer.json` require) |
| Composer | 2.x |
| PHP extensions | zip؛ pdo_sqlite برای suite پیش‌فرض |
| Git | برای clone |

**نسخه Laravel میزبان:** illuminate ^13 در require. **Testbench** ^11 در require-dev — سازگاری واقعی را با `composer install` و تست محلی بررسی کن (DEC-007).

## نصب

```bash
composer install
```

از lockfile موجود استفاده کن؛ تغییر ناخواسته `composer.lock` بدون تصمیم ممنوع.

## Testbench

پکیج با Orchestra Testbench در `tests/` اجرا می‌شود — **نیاز به پروژه میزبان (`karno-base`) نیست**.

## فرمان‌های رایج

| فرمان | هدف | موفقیت |
|--------|-----|--------|
| `vendor/bin/phpunit --exclude-group redis` | suite پیش‌فرض (مطابق CI) | خروج 0، OK در خروجی |
| `vendor/bin/phpunit --group redis` | یکپارچگی Redis | نیاز Redis + پیکربندی — اغلب skip |
| `vendor/bin/pint --test` | سبک کد | بدون diff |
| `composer validate --strict` | manifest | valid |
| `composer audit` | امنیت وابستگی | بررسی دستی؛ CI فعلاً `\|\| true` |
| `composer test` | alias phpunit | همان phpunit بدون exclude redis — **ترجیح CLI بالا برای هم‌خوانی CI** |

## SQLite

تست‌های feature/integration معمولاً SQLite در حافظه (Testbench) — migrationهای `database/migrations_squashed/`.

## MySQL / PostgreSQL / Redis (آینده)

برای WP-08-01/02 و گروه `redis`: محیط جدا، داده ساختگی، بدون production. متغیرهای محیط از config تست — فقط پس از خواندن `tests/` و `phpunit.xml.dist` در مخزن واقعی.

## محیط‌ها

| محیط | کاربرد |
|------|--------|
| dev محلی | توسعه + phpunit exclude redis |
| CI GitLab | همان خطوط `.gitlab-ci.yml` |
| staging live | فقط با مجوز — `@group live` |
| production | خارج دامنه CONTRIBUTING |

## خطاهای رایج

- ناسازگاری PHP: پیام composer platform
- Redis skip: طبیعی بدون سرویس
- OOM حافظه PHPUnit: افزایش `memory_limit` محلی
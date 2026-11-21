# CODING-AND-ARCHITECTURE-STANDARDS

منبع: کد فعلی + [ARCHITECTURE.md](../../../ARCHITECTURE.md) + step-05. **توصیه** جدا از **وضعیت موجود** مشخص می‌شود.

## سبک PHP

- PSR-4: `Karnoweb\Omnichannel\`
- قالب‌بندی: Laravel Pint — `vendor/bin/pint --test` در CI
- `strict_types`: طبق فایل‌های موجود هر ماژول؛ هنگام افزودن کد همان الگوی همسایه را دنبال کن

## مرز لایه‌ها

| لایه | مسئولیت |
|------|----------|
| Models / Eloquent | persistence دامنه omnichannel |
| Services (مثلاً `PublishingService`) | orchestration انتشار |
| Drivers | تطبیق API کانال |
| Contracts | API عمومی پکیج |
| Jobs / Commands | صف و عملیات |
| HTTP clients (`BaseApiClient`) | ارتباط خارجی + rate limit |

**ممنوع:** import مستقیم از `karno-base` یا مدل‌های میزبان — تست: `NoHostDependencyTest`, `PackageIsolationTest`.

## قرارداد عمومی

تغییر در امضا، نوع بازگشت، exception، رویداد، کلید config، serialization JSON، رفتار پیش‌فرض = بررسی breaking + CHANGELOG.

## خطا و وضعیت انتشار

- تفکیک خطای دائمی / موقت / UnknownOutcome
- عبور از state machine بدون `claim` مجاز نیست
- rate limit و idempotency — دور زدن قفل ممنوع

## صف

Jobها: deadline، retry metadata، جلوگیری از انتشار تکراری. retry شبکه‌ای بدون idempotency مجاز نیست.

## Migration

- مسیر: `database/migrations_squashed/` — نحوه load از `OmnichannelServiceProvider` را قبل از تغییر بخوان
- بدون بازنویسی migration منتشرشده
- rollback: فقط با بررسی `down()` و داده prod

## دامنه محصول

- انتشار آزاد مستقل از `product_id`
- batch: شکست یک مقصد کل batch را failed اعلام نکند (معیار WP-08-06)
- `catalog_sync.enabled` پیش‌فرض false

## شکاف → نقشه راه

تفاوت To-Be معماری با As-Is: بسته کار در ROADMAP — در این مرحله refactor ممنوع.
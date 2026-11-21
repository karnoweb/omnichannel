# FILE-INVENTORY

**استثنا:** `vendor/`, `.phpunit.cache/`, باینری — بدون بررسی خط‌به‌خط (شواهد: [WORKLOG.md](WORKLOG.md)).

## خلاصه پوشش

| محدوده | فایل PHP (تقریبی) | سطح فهرست | وضعیت بررسی |
|---|---|---|---|
| `src/` (کل) | 161 | per-file نمونه‌برداری + خواندن لایه‌های هسته | بررسی محتوا |
| `src/Console/` | 10 | per-file | بررسی |
| `config/` | 1 (`omnichannel.php`) | per-file | بررسی |
| `database/migrations_squashed/` | 10 | per-file | بررسی |
| `database/factories/` | چند | خلاصه | نمونه |
| `lang/en`, `lang/fa` | 2+ | per-locale | نمونه |
| `tests/Feature/` | 39 | پوشه | نگاشت TEST-REPORT |
| `tests/Unit/` | 2 | پوشه | نگاشت |
| `tests/Architecture/` | 3 | پوشه | بررسی |
| `tests/Drivers/` | 1 | پوشه | بررسی |
| `tests/Integration/` | 1 | پوشه | redis excluded |

## `src/` به تفکیک پوشه

| پوشه | تعداد تقریبی | مسئولیت |
|---|---|---|
| `Channels/` | ~۵۰ | ۹ درایور + Messaging + transformers |
| `Services/` | ~۳۵ | domain services |
| `Models/` | ~۱۲ | Eloquent + phase 3 models |
| `Http/` | ~۸ | ApiClient, media bytes, rate limit |
| `Jobs/` | ~۴ | queue |
| `Console/` | 10 | CLI |
| `Contracts/` | ~۲۰ | host + driver contracts |
| `Events/`, `Exceptions/`, `Enums/`, `Data/`, `DTOs/`, `Results/`, `ValueObjects/` | بقیه | domain primitives |

## Migrations squashed (بررسی شده)

| فایل | نقش |
|---|---|
| `2026_11_19_000000_create_omnichannel_schema.php` | اسکیما پایه |
| `2026_11_20_000001`–`000004` | فاز ۲ tokens/history |
| `2026_11_21_000001` | publication sources |
| `2026_11_21_000002` | media tables |
| `2026_11_21_000003` | batches |
| `2026_11_21_000004` | taxonomy |
| `2026_11_21_000005` | catalog_sync_states |

## یافته‌های مرتبط

- [OMC-AUD-007](FINDINGS.md#omc-aud-007) — تست ارتقا migrations 13.3
- [OMC-AUD-004](FINDINGS.md#omc-aud-004) — بدون git history per-file

جزئیات API عمومی تکرار نشده — [PUBLIC-API-INVENTORY.md](PUBLIC-API-INVENTORY.md).
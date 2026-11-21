# CI-AND-QUALITY-GATES

فایل: [`.gitlab-ci.yml`](../../../.gitlab-ci.yml) — **در ۴.۸ تغییر نکرد.**

## جدول کنترل

| کنترل | موجود | هدف | شکاف | WP/اقدام |
|--------|--------|-----|------|-----------|
| Pint test | بله | هر push | — | — |
| PHPUnit no redis | بله | هر push | Redis جدا | WP-05-06 |
| composer validate | بله | هر push | — | — |
| composer audit | `\|\| true` | مسدودکننده اختیاری | شکست مخفی | تصویب maintainer |
| MySQL migrate test | خیر | pre-release | بله | WP-08-01 |
| PG migrate | خیر | pre-release | بله | WP-08-02 |
| Architecture subset | در suite | هر push | — | — |
| DOC link check | خیر | پیشنهاد | — | آینده |
| CHANGELOG gate | خیر | release | دستی | RELEASE-WORKFLOW |

## دروازه‌ها (ارجاع)

[QUALITY-GATES](../step-07/QUALITY-GATES.md) فاز ۵–۹.

## توقف خط

- شکست Pint یا PHPUnit: **ادغام ممنوع** (پیشنهاد)
- audit: آگاهی تا تغییر سیاست
- نقص امنیتی مسدودکننده: بدون استثناء مالک

## استثناء

ثبت: شناسه، دلیل، شاهد، مسئول پذیرش، تاریخ بازبینی.

CI سبز + redis excluded ≠ آمادگی تولید.
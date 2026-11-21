# مرحله ۴.۳ — ماتریس قابلیت و شکاف

| فیلد | مقدار |
|---|---|
| نسخه مبنا | 13.3.0 |
| تاریخ | 2026-11-21 |
| وضعیت | **تکمیل جزئی** — تحلیل کامل مستند؛ live/میزبان مسدود |
| پیش‌نیاز | [step-01](../step-01/README.md), [step-02](../step-02/README.md), [providers](../../providers/README.md) |

## روش مطالعه

1. [CAPABILITY-MATRIX.md](CAPABILITY-MATRIX.md) — تصویر میان‌کانالی
2. [DRIVER-CAPABILITIES.md](DRIVER-CAPABILITIES.md) → `drivers/`
3. [CORE-CAPABILITIES.md](CORE-CAPABILITIES.md) — هسته
4. [GAP-REGISTER.md](GAP-REGISTER.md) + [PRIORITIZATION.md](PRIORITIZATION.md)
5. [PRD-INPUTS.md](PRD-INPUTS.md) — ورودی ۴.۴

## فهرست خروجی‌ها

| فایل | وضعیت |
|---|---|
| README.md | این سند |
| CAPABILITY-MATRIX.md | تکمیل |
| DRIVER-CAPABILITIES.md + drivers/ (۹) | تکمیل |
| CORE-CAPABILITIES.md | تکمیل |
| API-CONTRACT-COMPARISON.md | تکمیل |
| EVIDENCE-REGISTRY.md | تکمیل |
| GAP-REGISTER.md | تکمیل (۱۵ شکاف) |
| PRIORITIZATION.md | تکمیل |
| DEPENDENCY-MAP.md | تکمیل |
| VALIDATION-MATRIX.md | تکمیل |
| PRODUCT-DECISIONS.md | تکمیل |
| PRD-INPUTS.md | تکمیل |
| WORKLOG.md | تکمیل |
| CHECKLIST.md | تکمیل |

## آمار خلاصه

- `OMC-CAP`: 20 | `OMC-CORE`: 12 | `P43-GAP`: 15
- PHPUnit: 102 passed (P43-EV-001)
- Demo از آمار سرویس واقعی جدا است

## ادامه

مرحله **۴.۴ PRD** با استفاده از `PRD-INPUTS` و `PRODUCT-DECISIONS`.
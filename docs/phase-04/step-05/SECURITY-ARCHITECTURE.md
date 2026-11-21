# SECURITY-ARCHITECTURE

مرجع: `docs/hardening/SECURITY.md`.

## مدل تهدید (خلاصه)

| تهدید | کنترل As-Is |
|---|---|
| نشت توکن در log | `SecretRedactor`, `PublicationLoggerSecurityTest` |
| SSRF رسانه | `HttpMediaBytesResolver` + tests |
| دسترسی بین شعبه | `BranchScope`, `BranchIsolationTest` |
| پاسخ API حساس | `ApiResponseSecurityTest` |

## جداسازی

- `branch_id` روی connection/publication
- soft reference به مدل‌های میزبان (بدون FK سخت)

## مجوز

بر عهده درایور/میزبان — پکیج `UnsupportedCapabilityException` برای عملیات غیرمجاز.

## To-Be

- چک‌لیست استقرار: env secrets، disk خصوصی media
- live test توکن منقضی در VALIDATION-PLAN
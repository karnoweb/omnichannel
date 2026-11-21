# CHANGE-EVIDENCE — ریز تغییرات با شناسه

شناسه‌ها پایدار برای ردیابی بین `CHANGELOG`، `UPGRADING`، `docs/releases/` و مراحل بعدی فاز ۴.

## نسخه 13.2.0

| شناسه | دسته | شرح (مصرف‌کننده) | شواهد | Breaking | اقدام ارتقا | تست |
|--------|------|------------------|--------|----------|-------------|-----|
| REL-CHANGE-001 | Added | محدودیت نرخ هر درخواست API (`ApiRequestRateLimiter`) | `CHANGELOG` 13.2؛ `BaseApiClient` | خیر | بدون اقدام config اگر از قبل سالم | مستند: 79 تست فاز ۰۲ |
| REL-CHANGE-002 | Added | بازیابی انتشار (`PublicationRecoveryService`) | `CHANGELOG` 13.2 | خیر | آگاهی از log recovery | همان |
| REL-CHANGE-003 | Added | تطبیق کاتالوگ WhatsApp + job (auto-reconcile پیش‌فرض خاموش) | `CHANGELOG` 13.2؛ `hardening/UPGRADE-GUIDE` UnknownOutcome | خیر | بررسی انتشارات مبهم واتس‌اپ | همان |
| REL-CHANGE-004 | Added | فرمان‌های `omnichannel:doctor`, `health`, `prune-artifacts` | `CHANGELOG` 13.2 | خیر | ثبت در runbook عملیات | همان |
| REL-CHANGE-005 | Fixed | scope صحیح connection در rate limit برای update/delete/deactivate | `CHANGELOG` 13.2 | خیر | — | همان |
| REL-CHANGE-006 | Fixed | capture صحیح context در قفل rate limiter | `CHANGELOG` 13.2؛ OMC-AUD-001 در ممیزی ۴.۱ | خیر | — | همان |
| REL-CHANGE-007 | Docs | مستندات فاز ۰۲ و pipeline CI | `CHANGELOG` 13.2 | خیر | — | — |
| REL-CHANGE-008 | DB | `attempt_token`, history, retry deadline | migrations `2026_11_20_*` | خیر* | پشتیبان DB؛ توقف worker؛ [`hardening/UPGRADE-GUIDE`](../../hardening/UPGRADE-GUIDE.md) | Migration tests SQLite |

\* تغییر رفتاری حساس (UnknownOutcome واتس‌اپ، قیود حذف connection) در hardening مستند شده؛ شماره نسخه دقیق هر رفتار فقط با شواهد نسخه‌ای.

## نسخه 13.3.0

| شناسه | دسته | شرح | شواهد | Breaking | اقدام ارتقا | تست |
|--------|------|-----|--------|----------|-------------|-----|
| REL-CHANGE-010 | Added | منابع انتشار (product / free-form / reference)، `product_id` اختیاری | `CHANGELOG`؛ migration `2026_11_21_000001` | خیر | اجرای migration؛ مسیر `PublishingService::create()` محصول بدون تغییر رفتار اعلام‌شده | 102 تست فاز ۰۳ / ۴.۱ |
| REL-CHANGE-011 | Added | رسانه اختصاص انتشار + `omnichannel:prune-media` | migration `000002`؛ config `publication_media` | خیر | publish config در صورت سفارشی‌سازی | همان |
| REL-CHANGE-012 | Added | انتشار گروهی چند اتصال (`PublicationBatch`) | migration `000003`؛ config `publication_batch` | خیر | — | همان |
| REL-CHANGE-013 | Added | taxonomy بازارگاه + `CategoryMappingResolver` + `omnichannel:sync-taxonomy` | migration `000004` | خیر | Basalam: نگاشت DB | همان |
| REL-CHANGE-014 | Added | لایه `catalog_sync` (پیش‌فرض `enabled: false`) + job | migration `000005`؛ config | خیر | فعال‌سازی فقط با تصمیم میزبان | همان |
| REL-CHANGE-015 | Changed | `BasalamCategoryMapper` اولویت نگاشت DB | `CHANGELOG` 13.3 | خیر | بررسی metadata/config قدیمی | همان |
| REL-CHANGE-016 | Changed | `MediaItem` فیلدهای اختیاری `asset_id`, `checksum` | `CHANGELOG` 13.3 | خیر | سازگار با آرایه قدیمی | همان |
| REL-CHANGE-017 | Docs | فاز ۰۳ و `HOST-INTEGRATION-PHASE-03.md` | `CHANGELOG` 13.3 | خیر | — | — |

## نسخه‌های 13.0.0 / 13.1.0 (تاریخچه ناقص)

| شناسه | دسته | شرح | شواهد | وضعیت انتساب |
|--------|------|-----|--------|----------------|
| REL-CHANGE-020 | Hardening | گام‌های STEP 00–08 مقاوم‌سازی | `docs/hardening/IMPLEMENTATION-CHECKLIST.md`، `AUDIT.md` (13.0.0) | مستند تاریخی؛ بدون فهرست CHANGELOG ریشه |
| REL-CHANGE-021 | Baseline | نقطه پایه 13.1.0 برای فاز ۰۲ | `docs/phase-02/BASELINE-AUDIT.md` | شماره نسخه در گزارش؛ جزئیات انتشار نامشخص |

## مستثنیات صریح

- خروجی‌های **۴.۴ PRD** و **۴.۵ ARCHITECTURE (To-Be)** به هیچ `REL-CHANGE` منتشرشده‌ای نسبت داده نشده‌اند.
- قابلیت‌های «برنامه‌ریزی‌شده» در ماتریس P43-GAP بدون نسخه هدف در این فهرست نیستند.
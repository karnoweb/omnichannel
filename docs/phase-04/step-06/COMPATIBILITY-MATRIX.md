# COMPATIBILITY-MATRIX — سازگاری و اثر بر مصرف‌کننده

## جدول تغییرات با اثر (§۱۰)

| شناسه | رفتار قبلی | رفتار جدید | نوع اثر | مصرف‌کننده | اقدام ارتقا | شاهد |
|--------|------------|------------|---------|------------|-------------|------|
| REL-CHANGE-001 | بدون محدودیت یکنواخت per-request | Rate limit در `BaseApiClient` | عملیاتی | همه درایورهای HTTP | پایش خطای 429؛ تنظیم cache/Redis در صورت نیاز | CHANGELOG 13.2 |
| REL-CHANGE-003 | 2xx batch → Published (رفتار قدیمی ذکرشده در hardening) | UnknownOutcome برای batch واتس‌اپ | رفتاری | WhatsApp Catalog | بازبینی انتشارات مبهم؛ `omnichannel:inspect-whatsapp-batch` | `hardening/UPGRADE-GUIDE` |
| REL-CHANGE-008 | حذف connection/channel آزادتر | قیود حفظ history | داده/عملیات | همه | غیرفعال‌سازی به‌جای حذف | hardening STEP 07 |
| REL-CHANGE-010 | جریان محصول واحد | منابع انتشار + `product_id` nullable | گسترش API | میزبان با `PublishingService` | migration؛ بدون شکستن flow محصول اعلام‌شده | phase-03 UPGRADING |
| REL-CHANGE-014 | — | `catalog_sync.enabled` پیش‌فرض false | config | میزبان کاتالوگ | عمدی روشن کردن + metadata | config |

**تغییر ناسازگار اعلام‌شده در CHANGELOG 13.2/13.3:** موردی ثبت نشده. تغییرات رفتاری حساس (واتس‌اپ، قیود DB) **سازگار با نسخه فرعی** طبق شماره‌گذاری فعلی اما نیازمند اقدام عملیاتی دارند.

## ماتریس مسیر ارتقا (خلاصه)

| از | به | PHP/Laravel | قرارداد عمومی | config | DB | صف | میزبان | تست ارتقا | وضعیت |
|----|-----|-------------|---------------|--------|-----|-----|--------|-----------|--------|
| 13.1.0 | 13.2.0 | ^8.3 / ^13 | بدون breaking اعلامی | جدید محدود | migrations 11-20 | jobs جدید | CI/doc | **تأیید نشده** end-to-end | مستند تاریخی |
| 13.2.0 | 13.3.0 | ^8.3 / ^13 | بدون breaking اعلامی | کلیدهای 13.3 | migrations 11-21 | catalog sync job | HOST-INTEGRATION | `UpgradeFrom130Test` شکاف OMC-AUD-007 | جزئی SQLite |
| نصب تازه | 13.3.0 | ^8.3 / ^13 | کامل squashed | publish اختیاری | یک‌جا schema | همان | integrate کاتالوگ | نصب tinker/migrate | آزمون dev |

جزئیات گام‌ها: [UPGRADING.md](../../../UPGRADING.md)، [MIGRATION-REVIEW.md](MIGRATION-REVIEW.md).
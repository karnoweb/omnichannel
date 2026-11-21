# گزارش ممیزی مرحله ۴.۱ — نسخه 13.3.0

## خلاصه مدیریتی

پکیج `karnoweb/omnichannel` در نسخه **13.3.0** زیرساخت انتشار چندکاناله، منابع Product/FreeForm، رسانه اختصاصی، انتشار گروهی (batch)، نگاشت taxonomy و **اسکلت** همگام‌سازی کاتالوگ (پیش‌فرض خاموش) را در `src/` دارد. **102 تست** روی SQLite در این ممیزی **سبز** بود.

**آمادگی تولید:** **تأییدنشده (مشروط)** — مهندسی پکیج روی SQLite قابل قبول است؛ MySQL/PG، Redis در CI، API زنده، میزبان و UI **تأیید نشده**.

## Delta نسبت به `docs/hardening/AUDIT.md`

| موضوع | hardening (۱۳.۰.۰) | وضعیت ۱۳.۳.۰ |
|---|---|---|
| حجم کد `src/` | ~۱۰۸ فایل PHP | **~۱۶۱** فایل |
| تست | ۲ Architecture + scanner | **102** Feature/Unit/Architecture/Drivers/Integration |
| مدل‌ها | ۴ اصلی | + `PublicationMediaAsset`, `PublicationBatch*`, `ExternalCategory`, `CategoryMapping`, `CatalogSyncState` |
| منابع انتشار | فقط product | `PublicationSourceType`, `PublicationCreationService`, `product_id` nullable |
| ممیزی قبلی | SecretRedactor دستی | تست `PublicationLoggerSecurityTest`, `SecretRedactorTest` |
| یافته‌های بحرانی hardening | SSRF، شعبه، idempotency | **بازبینی نشده خط‌به‌خط**؛ فرض رفع جزئی با تست‌های فاز ۲ حفظ شده — رگرسیون در این ممیزی اثبات نشد |

یافته‌های تکراری را در [FINDINGS.md](FINDINGS.md) با شناسه جدید آورده‌ایم؛ برای جزئیات قدیمی به بخش‌های همان سند hardening مراجعه کنید.

## جدول ادعای فاز ۳

| ادعا (CHECKLIST / faz-3) | شاهد کد | شاهد تست | نتیجه |
|---|---|---|---|
| STEP 01 منابع انتشار | `PublicationCreationService`, migration `000001` | `FreeFormPublicationTest`, `LegacyProductPublicationTest`, … | **Verified** (SQLite) |
| STEP 02 رسانه | `PublicationMediaService`, `000002` | `PublicationMediaUploadTest`, … | **Verified** محدود |
| STEP 03 batch | `PublicationBatchService`, `000003` | `PublicationBatchCreationTest`, … | **Verified** محدود |
| STEP 04–05 taxonomy | `CategoryMapping`, `MarketplaceTaxonomyService` | `CategoryMappingTest`, … | **جزئی** — sync زنده ندارد |
| STEP 06 catalog sync | `CatalogSyncService`, `enabled=false` | `CatalogSyncDisabledTest` | **اسکلت** — Pending میزبان |
| STEP 07 میزبان | `HOST-INTEGRATION-PHASE-03.md` | — | **Pending** |
| STEP 08–11 ارتقا DB | migrations `000001`–`000005` | `FreshInstallationTest`; نه 13.3 upgrade اختصاصی | **جزئی** — [OMC-AUD-007](FINDINGS.md#omc-aud-007) |
| Operational gate (Live, MySQL) | — | — | **Blocked / Pending** |

## دامنه و روش

- خواندن سورس، config، migrations، تست‌ها و مستندات فاز ۲/۳
- اجرای PHPUnit و composer validate/audit/check-platform-reqs
- **بدون** تغییر کد اجرایی پکیج در ممیزی
- **بدون** درخواست به API خارجی

## ریسک‌های اصلی

| شناسه | عنوان | شدت |
|---|---|---|
| [OMC-AUD-009](FINDINGS.md#omc-aud-009) | DB غیر SQLite تأییدنشده | متوسط |
| [OMC-AUD-012](FINDINGS.md#omc-aud-012) | یکپارچگی میزبان | بالا (عملیاتی) |
| [OMC-AUD-007](FINDINGS.md#omc-aud-007) | تست ارتقا 13.3 | متوسط |
| [OMC-AUD-005](FINDINGS.md#omc-aud-005) | مستندات فاز ۳ ناقص | متوسط |

## محدودیت‌ها

- نبود Git در پوشه پکیج
- نبود `karno-base`
- lock file ناهمگام با `composer.json`
- Redis و `@group live` اجرا نشد

## پیوند شواهد

- [TEST-REPORT.md](TEST-REPORT.md)
- [IMPLEMENTATION-MATRIX.md](IMPLEMENTATION-MATRIX.md)
- [DRIVER-AUDIT.md](DRIVER-AUDIT.md)
- [WORKLOG.md](WORKLOG.md)
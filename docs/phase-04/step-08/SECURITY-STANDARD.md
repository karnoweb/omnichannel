# SECURITY-STANDARD

| حوزه | کنترل | آزمون / شاهد |
|------|--------|----------------|
| جداسازی شعب | publication/connection به branch صحیح | Feature tests؛ OMC-AUD-002 |
| اسرار | config/encrypted؛ نه log | تست پوشش راز در logger |
| لاگ | redact token/password | بررسی دستی + تست |
| رسانه/URL | SSRF، اندازه، نوع | media intake tests |
| HTTP | timeout، TLS | client config |
| صف | idempotency، قفل | concurrency tests هدف |
| catalog_sync | پیش‌فرض off | CatalogSyncDisabled tests |
| وابستگی | `composer audit` | CI فعلی غیرمسدودکننده — **شکاف** |

## داده آزمون

مصنوعی؛ بدون داده مشتری؛ بدون توکن واقعی در fixture.

## گزارش آسیب‌پذیری

گزارش خصوصی به maintainer — جزئیات exploit در issue عمومی ننویس.

## مسدودکننده پذیرش

نقص جداسازی شعب یا افشای اسرار در مسیر اصلی انتشار — بدون پذیرش ریسک مستند مالک.
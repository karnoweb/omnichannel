# NON-FUNCTIONAL-REQUIREMENTS

| NFR | حوزه | نیازمندی | معیار سنجش | وضعیت فعلی | اولویت |
|---|---|---|---|---|---|
| NFR-001 | امنیت | عدم لاگ توکن/secret | بازبینی کد/لاگ | پیاده پالایش | ضروری |
| NFR-002 | امنیت | SSRF در fetch رسانه | تست HttpMedia* | پیاده | ضروری |
| NFR-003 | امنیت | جداسازی داده شعبه | تست isolation | تست محلی | ضروری |
| NFR-004 | حریم | بدون ذخیره داده مشتری نهایی کانال | مرز دامنه | طراحی | مهم |
| NFR-005 | پایداری | rate limit قابل تنظیم | config per channel | پیاده | مهم |
| NFR-006 | پایداری | queue برای انتشار | job retry policy میزبان | پیاده | ضروری |
| NFR-007 | کارایی | debounce catalog sync | config seconds | پیاده | اختیاری |
| NFR-008 | نگهداری | قرارداد توسعه درایور پایدار | DriverContractTest | 102 tests | ضروری |
| NFR-009 | آزمون | suite بدون redis در CI | phpunit sqlite | 102 OK | ضروری |
| NFR-010 | آزمون | پوشش MySQL/PG | job CI چند DB | **ندارد** OMC-AUD-009 | مهم |
| NFR-011 | آزمون | گروه live taxonomy | تست برچسب‌دار | **ندارد** | متوسط |
| NFR-012 | i18n | lang فارسی capability | `lang/` | جزئی | مهم |
| NFR-013 | مستندات | فارسی فنی برای dev | docs phase-04 | در حال تکمیل | ضروری |
| NFR-014 | سازگاری | PHP ^8.3, Illuminate ^13 | composer.json | اعلام | ضروری |
| NFR-015 | سازگاری | استقلال از karno-base (P-01) | بدون require host | برقرار | ضروری |
| NFR-016 | ارتقا | migration idempotent | tests جزئی | شکاف FR-038 | مهم |
| NFR-017 | بهره‌برداری | عدم crash خاموش در خطای API | exceptions typed | پیاده | مهم |
| NFR-018 | محرمانگی | redact URL دارای credential | API comparison doc | بررسی دستی | مهم |
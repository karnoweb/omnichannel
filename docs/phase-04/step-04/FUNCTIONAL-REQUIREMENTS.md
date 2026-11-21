# FUNCTIONAL-REQUIREMENTS

قالب خلاصه: شناسه | عنوان | تصمیم دامنه | اولویت | شواهد فعلی | سناریوی پذیرش (خلاصه)

## ۶.۱ اتصال و کانال

| FR | عنوان | دامنه | اولویت | وضعیت فعلی | پذیرش |
|---|---|---|---|---|---|
| FR-001 | ثبت و resolve درایور از config | مصوب طراحی | ضروری | `ChannelManager` | درایور ناشناخته → خطای مشخص |
| FR-002 | ذخیره Connection با metadata شعبه | مصوب | ضروری | مدل connection | داده شعبه A به B نشت نکند |
| FR-003 | اعتبارسنجی توکن قبل از انتشار | مصوب | ضروری | per driver test | اتصال نامعتبر → انتشار متوقف |
| FR-004 | گزارش `capabilities()` سازگار با رفتار | مصوب | مهم | drivers | عملی unsupported → استثنای قرارداد |
| FR-005 | اتصال غیرفعال → عملیات ممنوع | پیشنهادی | مهم | جزئی | درخواست روی inactive → خطا بدون تماس API |

## ۶.۲ انتشار

| FR | عنوان | دامنه | اولویت | وضعیت | پذیرش |
|---|---|---|---|---|---|
| FR-006 | انتشار FreeForm بدون محصول (P-02) | مصوب | ضروری | پیاده | Product لازم نباشد |
| FR-007 | انتشار Product-sourced | مصوب | ضروری | پیاده | داده از host contract |
| FR-008 | تبدیل قالب به فرمت درایور | مصوب | ضروری | drivers | محدودیت caption رعایت شود |
| FR-009 | زمان‌بندی انتشار | مصوب | ضروری | job/command | زمان due → dispatch |
| FR-010 | ویرایش پس از انتشار در حد پشتیبانی | مصوب | مهم | per capability | IG/WA/TG متفاوت |
| FR-011 | حذف یا deactivate جایگزین | مصوب | مهم | capabilities | Basalam deactivate |
| FR-012 | idempotency intent انتشار | مصوب | مهم | batch docs | تکرار intent موفق دوباره publish نکند¹ |
| FR-013 | تمایز پذیرش صف vs موفقیت سرویس | پیشنهادی | ضروری | جزئی | وضعیت publication شفاف |

¹ میزبان `intent_id` — HOST-INTEGRATION.

## ۶.۳ Batch

| FR | عنوان | دامنه | اولویت | وضعیت | پذیرش |
|---|---|---|---|---|---|
| FR-014 | Batch با وضعیت مستقل هر Publication (P-03) | مصوب | ضروری | `PublicationBatchService` | یک شکست همه را مخفی نکند |
| FR-015 | retry مقصد ناموفق بدون تکرار موفق | پیشنهادی | مهم | جزئی | سناریوی batch partial |
| FR-016 | گزارش نتیجه گروهی به مصرف‌کننده | مصوب | مهم | API batch | موفق/جزئی/شکست قابل خواندن |

## ۶.۴ رسانه

| FR | عنوان | دامنه | اولویت | وضعیت | پذیرش |
|---|---|---|---|---|---|
| FR-017 | پذیرش `omc-asset:{id}` | مصوب | ضروری | phase-03 decision | resolve از contract میزبان |
| FR-018 | محدودیت نوع/حجم per channel | مصوب | مهم | MessengerLimits | oversize → خطای validation |
| FR-019 | بارگذاری HTTP legacy با SSRF guard | مصوب | ضروری | `HttpMediaBytesResolver` | URL داخلی رد شود |
| FR-020 | شکست دانلود رسانه → انتشار متوقف | مصوب | مهم | tests | خطای قابل گزارش |

## ۶.۵ کاتالوگ و محصول

| FR | عنوان | دامنه | اولویت | وضعیت | پذیرش |
|---|---|---|---|---|---|
| FR-021 | نگاشت دسته با اولویت phase-03 | مصوب | مهم | taxonomy service | override > DB > config |
| FR-022 | انتشار listing در Basalam/WA | مصوب | ضروری | drivers | product fields معتبر |
| FR-023 | به‌روزرسانی قیمت/موجودی در کانال | مصوب | مهم | capability | از میزبان، نه overwrite بی‌قصد |
| FR-024 | حفظ مرجعیت قیمت/موجودی میزبان (P-04) | مصوب | ضروری | `CatalogSyncPolicy` | sync بیرون→میزبان ممنوع بدون policy |
| FR-025 | deactivate به‌جای delete در Basalam | مصوب | مهم | driver reason | delete unsupported اعلام |

## ۶.۶ همگام‌سازی

| FR | عنوان | دامنه | اولویت | وضعیت | پذیرش |
|---|---|---|---|---|---|
| FR-026 | catalog sync خاموش پیش‌فرض (P-05) | مصوب | ضروری | config false | نصب/ارتقا خودکار on نشود |
| FR-027 | فعال‌سازی sync سراسری + per connection | مصوب | مهم | metadata flag | هر دو لازم |
| FR-028 | trigger sync از رویداد میزبان | مشروط | مهم | contract | بدون wire میزبان → no-op |
| FR-029 | reconcile اختیاری WhatsApp | پیشنهادی | اختیاری | service off | command صریح |

## ۶.۷ صف و خطا

| FR | عنوان | دامنه | اولویت | وضعیت | پذیرش |
|---|---|---|---|---|---|
| FR-030 | انتشار async via queue | مصوب | ضروری | `PublishPublicationJob` | queue config |
| FR-031 | rate limit per channel | مصوب | مهم | `ApiRequestRateLimiter` | سقف → انتظار/خطا |
| FR-032 | retry خطای موقت | پیشنهادی | مهم | clients | بدون duplicate بی‌مجوز |
| FR-033 | خطا بدون افشای secret | مصوب | ضروری | exceptions | token در message نباشد |
| FR-034 | reconciliation وضعیت مبهم WA | پیشنهادی | اختیاری | reconcile service | batch unknown → query |

## ۶.۸ مصرف‌کننده و توسعه

| FR | عنوان | دامنه | اولویت | وضعیت | پذیرش |
|---|---|---|---|---|---|
| FR-035 | نصب service provider و migration | مصوب | ضروری | package | publish migrate اجرا شود |
| FR-036 | health/diagnostic commands | پیشنهادی | اختیاری | artisan | تست اتصال |
| FR-037 | توسعه درایور جدید از `ChannelDriver` | مصوب | مهم | Demo | capabilities اجباری |
| FR-038 | مسیر ارتقا 13.2→13.3 | پیشنهادی | مهم | gap OMC-AUD-007 | migrationهای 2026_11_21 اعمال |
| FR-039 | registrar منبع Reference | مشروط | اختیاری | OMC-AUD-006 | بدون registrar → خطای روشن |

## ۶.۹ مشروط / خارج دامنه

| FR | عنوان | دامنه | اولویت | وضعیت | پذیرش |
|---|---|---|---|---|---|
| FR-040 | Webhook دریافت رخداد | نیازمند تصمیم | تعویق | ندارد | DEC آینده |
| FR-041 | Instagram DM / inbox | خارج | — | unsupported reason | خارج دامنه |
| FR-042 | WhatsApp messaging 24h | خارج | — | driver | catalog only (DEC-004) |
| FR-043 | سفارش/حمل بازارگاه | خارج | — | — | خارج دامنه |
| FR-044 | آمار/insights شبکه اجتماعی | آینده | پایین | ندارد | نیازمند تحقیق |

**جمع:** 44 نیازمندی یکتا (شامل موارد خارج از دامنه صریح).
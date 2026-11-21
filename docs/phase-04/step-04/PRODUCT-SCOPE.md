# PRODUCT-SCOPE — دامنه محصول

## مخاطبان

| نقش | مسئله | خروجی مورد انتظار |
|---|---|---|
| توسعه‌دهنده مصرف‌کننده پکیج | یکپارچه‌سازی Laravel | API سرویس‌ها، قراردادها، تست |
| توسعه‌دهنده درایور | افزودن کانال | `ChannelDriver`, کلاینت HTTP |
| مسئول میزبان | قیمت/موجودی، UI | bind قراردادهای host |
| اپراتور کسب‌وکار | انتشار چندکاناله | رفتار از طریق میزبان (غیرمستقیم) |
| DevOps/QA | استقرار، آزمون | صف، migration، CI |

## اهداف محصول

1. کتابخانه چندکاناله **مستقل از UI میزبان** برای انتشار و کاتالوگ.
2. قرارداد یکسان برای Publication/Batch/رسانه با تفاوت شفاف درایورها.
3. مرجعیت داده commerce در میزبان؛ پکیج orchestrator.
4. مستندسازی فارسی و ردیابی قابلیت per سرویس.

## غیرهدف‌ها

- پنل مدیریتی فروشگاه یا اپراتور داخل پکیج (P-06).
- جایگزینی ERP/فروشگاه میزبان.
- پشتیبانی یکسان همه APIهای هر سرویس‌دهنده.
- CRM کامل، inbox یکپارچه، webhook عمومی (فعلاً خارج دامنه — §۶.۹).
- تضمین آمادگی تولید بدون live validation.

## دامنه فعلی (وضعیت 13.3.0)

| حوزه | وضعیت | شاهد | تست |
|---|---|---|---|
| ۹ درایور + Demo | پیاده | `src/Channels/` | DriverContract 102 tests |
| انتشار Product/FreeForm | کامل | `PublicationCreationService` | Feature |
| Batch چند اتصال | کامل | `PublicationBatchService` | Batch tests |
| زمان‌بندی | کامل | command/job | Scheduled tests |
| رسانه omc-asset | کامل | `PublicationMediaService` | Media* |
| Taxonomy mapping | جزئی | DB + command | Category* |
| Catalog sync | اسکلت، **off** | `catalog_sync.enabled=false` | CatalogSync* |
| WA reconcile | اختیاری | service + config | mock |
| Reference source | بدون registrar پیش‌فرض | OMC-AUD-006 | Validation test |
| Host UI | خارج پکیج | HOST-INTEGRATION | — |
| Live سرویس‌ها | تأییدنشده | VALIDATION-MATRIX 4.3 | mock |

## دامنه مقصد (پیشنهادی — غیرمصوب)

| حوزه | سطح تعهد پیشنهادی | اولویت | پیش‌نیاز |
|---|---|---|---|
| پایداری قرارداد Meta/IG/WA | مشروط | P1 | DEC-001,006 |
| Basalam status رسمی | مشروط | P1 | P43-GAP-004 |
| سطح پشتیبانی RB/GP/ET | best-effort یا doc | P1 | DEC-005,002 |
| catalog_sync فعال‌سازی آگاهانه | مصوب طراحی فاز۳ | P2 | میزبان events |
| Reference source نمونه میزبان | اختیاری | P3 | registrar |
| ارتقا 13.2→13.3 مستند + تست | ضروری فنی | P1 | FR-038 |
| مستندات/CHANGELOG/roadmap کامل | الزام مالک | P2 | DOC-001..004 |

## اصول محصول (P-01..P-06)

| شناسه | اصل | وضعیت در PRD |
|---|---|---|
| P-01 | استقلال از karno-base | تأییدشده با شاهد معماری |
| P-02 | انتشار بدون محصول | تأیید FreeForm |
| P-03 | وضعیت مستقل Publication | تأیید DECISIONS phase-03 |
| P-04 | مرجعیت قیمت/موجودی میزبان | تأیید CatalogSyncPolicy |
| P-05 | catalog sync opt-in | تأیید config default false |
| P-06 | بدون UI میزبان | غیرهدف |

## مرز مسئولیت

| موضوع | پکیج | میزبان | سرویس | اپراتور |
|---|---|---|---|---|
| توکن اتصال | ذخیره امن، استفاده در درایور | وارد کردن در UI میزبان | صدور/ابطال | — |
| داده محصول | دریافت via contract | منبع حقیقت | نمایش در کانال | ویرایش در میزبان |
| رسانه بایت | resolve قرارداد | آپلود، storage | میزبانی CDN خارجی | — |
| صف/scheduler | jobهای پکیج | infra | — | مانیتور |
| خطا به کاربر | exception/API پکیج | ترجمه/UI | خطای API | اقدام |
| مجوز API | تشخیص عدم پشتیبانی | — | اعطا | درخواست |
# PROVIDER-SUPPORT-POLICY

تفکیت «سرویس ارائه می‌دهد» و «پکیج پشتیبانی می‌کند». جزئیات فنی: [providers/](../../providers/) و [step-03/drivers/](../step-03/drivers/).

## مدل سطوح

| سطح | معنی |
|---|---|
| **A — پشتیبانی هدفمند** | درایور + تست محلی + مرجع رسمی قابل اتکا |
| **B — مشروط** | پیاده‌سازی موجود؛ محدودیت حساب/مجوز/نسخه |
| **C — best-effort** | کد و تست mock؛ شواهد رسمی ضعیف |
| **D — آزمایشی** | Demo فقط |

## جدول سرویس‌ها

| سرویس | نوع | سطح فعلی | مقصد پیشنهادی | قابلیت کلیدی | محدودیت مهم | شاهد |
|---|---|---|---|---|---|---|
| Instagram | اجتماعی | B | B→A | Post, Carousel, Reel | حذف، نسخه Graph، نرخ | drivers/instagram |
| Telegram | پیام‌رسان | A | A | Message, Album, Pin | زمان‌بندی بومی ندارد | drivers/telegram |
| WhatsApp Catalog | کاتالوگ | B | B | Product batch | بدون messaging | drivers/whatsapp-catalog |
| Basalam | بازارگاه | A/B | A | Product, taxonomy | delete→deactivate | drivers/basalam |
| Bale | پیام‌رسان | A/B | A | Message, Album | کلاینت مشترک TG | drivers/bale |
| Rubika | پیام‌رسان | C | C یا B¹ | Message | مستند ناقص | drivers/rubika |
| Gap | پیام‌رسان | C | C یا B¹ | Message | مستند جزئی | drivers/gap |
| Eitaa | درگاه | C | مشروط² | Message | eitaayar | drivers/eitaa |
| Demo | داخلی | D | D | شبیه‌سازی | بدون API | drivers/demo |

¹ پس از تکمیل شواهد رسمی (DEC-005).  
² پس از تصمیم حقوقی/فنی (DEC-002).

## خانواده‌های قابلیت مشترک

| خانواده | سیاست |
|---|---|
| اتصال/توکن | همه درایورها: رفتار خطای مشخص |
| انتشار فوری/زمان‌بندی | زمان‌بندی توسط پکیج (OMC-CAP-012) |
| ویرایش/حذف | per `capabilities()` — بدون ادعای یکسان |
| کاتالوگ/قیمت | فقط WA, BS (+ هسته sync اختیاری) |
| Webhook/rخداد | خارج دامنه فعلی؛ نیاز تصمیم |
| همگام‌سازی خودکار | opt-in (P-05) |

## تغییر نسخه یا قطع سرویس

- نسخه API: اعلام در `providers/VERSION-AND-ACCESS-POLICY`؛ ارتقا Graph نیاز DEC-006.
- قطع درایور: فقط با تصمیم مالک، مسیر مهاجرت در ROADMAP ۴.۷ (هنوز تعریف نشده).
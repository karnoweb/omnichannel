# مرجع سرویس‌دهنده — WhatsApp Catalog (Meta Graph) (`whatsapp`)

**تاریخ پژوهش:** 2026-11-21 | **نسخه پکیج:** 13.3.0 | **وضعیت مرحله:** تکمیل جزئی با محدودیت (در صورت ذکر شکاف رسمی)

## ۶.۱ شناسنامه و منابع رسمی

- **نام رسمی:** WhatsApp Catalog (Meta Graph)
- **سازمان:** Meta
- **کاربرد در Omnichannel:** انتشار/کاتالوگ/پیام‌رسانی بسته به درایور
- **پورتال‌ها:** https://developers.facebook.com/docs/commerce-platform/catalog — Graph `graph.facebook.com`
- **منابع اصلی:** WA-S001, WA-S002, WA-S003

## ۶.۲ راه‌اندازی و احراز هویت

جزئیات مقایسه‌ای در [AUTHENTICATION.md](AUTHENTICATION.md). خلاصه این سرویس در جدول عملیات و منابع بالا.

## ۶.۳ اطلس قابلیت‌ها و انواع API

| شناسه قابلیت | خانواده API | قابلیت | شرح فنی | حساب/مجوز | وضعیت رسمی | Omnichannel | منابع |
|---|---|---|---|---|---|---|---|
| WA-CAP-001 | Catalog | items_batch | CREATE/UPDATE/DELETE محصول | Catalog ID + token | رسمی ـ مشروط | WhatsAppCatalogClient | WA-S001 |
| WA-CAP-002 | Catalog | check_batch_request_status | پیگیری handle | همان | رسمی ـ عمومی | poll/reconcile | WA-S002 |
| WA-CAP-003 | Catalog | products filter | retailer_id | همان | رسمی ـ عمومی | findByRetailerId | WA-S003 |

## ۶.۴ مرجع دقیق عملیات API

| شناسه عملیات | هدف | روش | آدرس پایه | مسیر/نسخه | احراز/مجوز | ورودی/خروجی | منبع | وضعیت |
|---|---|---|---|---|---|---|---|---|
| WA-OP-001 | batch mutation | POST | graph.facebook.com | /{version}/{catalog_id}/items_batch | Bearer | item_type, requests JSON | WA-S001 | full_official |
| WA-OP-002 | batch status | GET | graph.facebook.com | /{version}/{catalog_id}/check_batch_request_status | Bearer | handle | WA-S002 | full_official |
| WA-OP-003 | lookup product | GET | graph.facebook.com | /{version}/{catalog_id}/products | Bearer | filter retailer_id | WA-S003 | full_official |

## ۶.۵ رسانه و محتوا

batch_max_items 5000 در config؛ تصاویر محصول طبق Commerce Platform.

## ۶.۶ محدودیت نرخ، خطا و قابلیت اطمینان

Rate limit مبتنی بر catalog ID [WA-S001]؛ پکیج internal 60/h.

## ۶.۷ رخدادها و همگام‌سازی

Catalog webhooks items_batch — reconcile اختیاری off.

## ۶.۸ هزینه، شرایط بهره‌برداری و امنیت عملیاتی

Commerce/WhatsApp Business؛ IRR در config — تأیید منطقه‌ای باز.

## ۶.۹ وضعیت فعلی پکیج و اختلاف‌های مستند

| شناسه | رفتار رسمی | کد فعلی | تست ممیزی ۴.۱ | اختلاف | شاهد |
|---|---|---|---|---|---|
| WA-OP-001 | items_batch paths | هم‌خوان WhatsAppCatalogClient | WhatsAppCatalogBatch* | — | src |



## ۶.۱۰ سناریوهای اعتبارسنجی آینده و پرسش‌های باز

| شناسه سناریو | قابلیت | محیط | پیش‌نیاز | انتظار | جانبی/پاکسازی | وضعیت |
|---|---|---|---|---|---|---|
| WA-VAL-001 | batch lifecycle | Catalog + token | — | Finished | حذف test SKU | برنامه‌ریزی‌شده |

### پرسش‌های باز

| شناسه | اولویت | پرسش | دلیل عدم قطعیت | اثر |
|---|---|---|---|---|
| P42-Q-005 | P1 | تأیید IRR و Commerce eligibility | منطقه | قیمت‌گذاری |

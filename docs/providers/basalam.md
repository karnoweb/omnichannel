# مرجع سرویس‌دهنده — باسلام Open API (`basalam`)

**تاریخ پژوهش:** 2026-11-21 | **نسخه پکیج:** 13.3.0 | **وضعیت مرحله:** تکمیل جزئی با محدودیت (در صورت ذکر شکاف رسمی)

## ۶.۱ شناسنامه و منابع رسمی

- **نام رسمی:** باسلام Open API
- **سازمان:** باسلام
- **کاربرد در Omnichannel:** انتشار/کاتالوگ/پیام‌رسانی بسته به درایور
- **پورتال‌ها:** openapi.basalam.com؛ auth.basalam.com [BS-S001]
- **منابع اصلی:** BS-S001, BS-S002, BS-S003

## ۶.۲ راه‌اندازی و احراز هویت

جزئیات مقایسه‌ای در [AUTHENTICATION.md](AUTHENTICATION.md). خلاصه این سرویس در جدول عملیات و منابع بالا.

## ۶.۳ اطلس قابلیت‌ها و انواع API

| شناسه قابلیت | خانواده API | قابلیت | شرح فنی | حساب/مجوز | وضعیت رسمی | Omnichannel | منابع |
|---|---|---|---|---|---|---|---|
| BS-CAP-001 | Identity | users/me | پروفایل | OAuth vendor | رسمی ـ عمومی | BasalamApiClient::me | BS-S001 |
| BS-CAP-002 | Products | CRUD محصول | /v1/products | scopes product | رسمی ـ مشروط | BasalamDriver | BS-S001 |
| BS-CAP-003 | Taxonomy | categories | /v1/categories | read | رسمی ـ عمومی | SyncMarketplaceTaxonomy | BS-S001 |
| BS-CAP-004 | Files | upload /v1/files | product.photo | write | رسمی ـ عمومی | BasalamMediaUploader | BS-S001 |

## ۶.۴ مرجع دقیق عملیات API

| شناسه عملیات | هدف | روش | آدرس پایه | مسیر/نسخه | احراز/مجوز | ورودی/خروجی | منبع | وضعیت |
|---|---|---|---|---|---|---|---|---|
| BS-OP-001 | me | GET | openapi.basalam.com | /v1/users/me | Bearer | — | BS-S001 | full_official |
| BS-OP-002 | create product | POST | openapi.basalam.com | /v1/vendors/{vendorId}/products | Bearer | payload | BS-S001 | full_official |
| BS-OP-003 | upload file | POST | openapi.basalam.com | /v1/files | Bearer multipart | file_type | BS-S001 | full_official |

## ۶.۵ رسانه و محتوا

حدود name/description در config؛ تا ۱۰ عکس.

## ۶.۶ محدودیت نرخ، خطا و قابلیت اطمینان

rate 60/min internal؛ رسمی در پورتال.

## ۶.۷ رخدادها و همگام‌سازی

وب‌هوک در صورت وجود — خارج از publish اصلی.

## ۶.۸ هزینه، شرایط بهره‌برداری و امنیت عملیاتی

OAuth غرفه؛ وضعیت‌های `product_status.published` / `draft` در config (پیش‌فرض env 2976/3790) — P42-DIFF-003؛ deactivate فقط انتقال به draft است، نه حذف محصول.

## ۶.۹ وضعیت فعلی پکیج و اختلاف‌های مستند

| شناسه | رفتار رسمی | کد فعلی | تست ممیزی ۴.۱ | اختلاف | شاهد |
|---|---|---|---|---|---|
| BS-OP-001 | GET /v1/users/me | BasalamApiClient::me | Feature mock | — | BS-S001 |



## ۶.۱۰ سناریوهای اعتبارسنجی آینده و پرسش‌های باز

| شناسه سناریو | قابلیت | محیط | پیش‌نیاز | انتظار | جانبی/پاکسازی | وضعیت |
|---|---|---|---|---|---|---|
| BS-VAL-001 | createProduct | غرفه OAuth | scopes | product id | draft | برنامه‌ریزی‌شده |

### پرسش‌های باز

| شناسه | اولویت | پرسش | دلیل عدم قطعیت | اثر |
|---|---|---|---|---|
| P42-Q-004 | P1 | معتبر بودن status codes 2976/3790 | فقط config | deactivate |

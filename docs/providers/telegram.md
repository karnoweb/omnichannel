# مرجع سرویس‌دهنده — Telegram Bot API (`telegram`)

**تاریخ پژوهش:** 2026-11-21 | **نسخه پکیج:** 13.3.0 | **وضعیت مرحله:** تکمیل جزئی با محدودیت (در صورت ذکر شکاف رسمی)

## ۶.۱ شناسنامه و منابع رسمی

- **نام رسمی:** Telegram Bot API
- **سازمان:** Telegram FZ-LLC
- **کاربرد در Omnichannel:** انتشار/کاتالوگ/پیام‌رسانی بسته به درایور
- **پورتال‌ها:** https://core.telegram.org/bots/api
- **منابع اصلی:** TG-S001, TG-S002

## ۶.۲ راه‌اندازی و احراز هویت

جزئیات مقایسه‌ای در [AUTHENTICATION.md](AUTHENTICATION.md). خلاصه این سرویس در جدول عملیات و منابع بالا.

## ۶.۳ اطلس قابلیت‌ها و انواع API

| شناسه قابلیت | خانواده API | قابلیت | شرح فنی | حساب/مجوز | وضعیت رسمی | Omnichannel | منابع |
|---|---|---|---|---|---|---|---|
| TG-CAP-001 | Messages | sendMessage | ارسال متن | Bot token | رسمی ـ عمومی | BotApiDriver | TG-S001 |
| TG-CAP-002 | Media | sendMediaGroup | آلبوم ۲–۱۰ | Bot token | رسمی ـ عمومی | TelegramDriver | TG-S001 |
| TG-CAP-003 | Updates | webhook/getUpdates | دریافت آپدیت | Bot | رسمی ـ عمومی | خارج از دامنه publish | TG-S001 |

## ۶.۴ مرجع دقیق عملیات API

| شناسه عملیات | هدف | روش | آدرس پایه | مسیر/نسخه | احراز/مجوز | ورودی/خروجی | منبع | وضعیت |
|---|---|---|---|---|---|---|---|---|
| TG-OP-001 | getMe | POST | api.telegram.org | /bot<token>/getMe | token در path | — | TG-S002 | full_official |
| TG-OP-002 | sendMessage | POST | api.telegram.org | /bot<token>/sendMessage | token | chat_id, text | TG-S001 | full_official |
| TG-OP-003 | sendMediaGroup | POST | api.telegram.org | /bot<token>/sendMediaGroup | token | media[] | TG-S001 | full_official |
| TG-OP-004 | deleteMessage | POST | api.telegram.org | /bot<token>/deleteMessage | token | chat_id, message_id | TG-S001 | full_official |

## ۶.۵ رسانه و محتوا

متن ۴۰۹۶ کاراکتر؛ caption ۱۰۲۴؛ فایل URL تا ~۲۰MB ویدئو (مرجع Bot API).

## ۶.۶ محدودیت نرخ، خطا و قابلیت اطمینان

خطای 429 Too Many Requests؛ Retry-After در برخی پاسخ‌ها [TG-S001].

## ۶.۷ رخدادها و همگام‌سازی

getUpdates long polling یا setWebhook — پکیج فقط outbound publish.

## ۶.۸ هزینه، شرایط بهره‌برداری و امنیت عملیاتی

بات رایگان؛ MTProto جدا از Bot API.

## ۶.۹ وضعیت فعلی پکیج و اختلاف‌های مستند

| شناسه | رفتار رسمی | کد فعلی | تست ممیزی ۴.۱ | اختلاف | شاهد |
|---|---|---|---|---|---|
| TG-OP-002 | POST bot{token}/method | BotApiClient همان الگو | Feature | — | Messaging/BotApiClient.php |



## ۶.۱۰ سناریوهای اعتبارسنجی آینده و پرسش‌های باز

| شناسه سناریو | قابلیت | محیط | پیش‌نیاز | انتظار | جانبی/پاکسازی | وضعیت |
|---|---|---|---|---|---|---|
| TG-VAL-001 | sendMediaGroup | بات + کانال | admin | message_id[] | delete | برنامه‌ریزی‌شده |

### پرسش‌های باز

| شناسه | اولویت | پرسش | دلیل عدم قطعیت | اثر |
|---|---|---|---|---|
| P42-Q-008 | P2 | سقف دقیق 429 per chat | نیاز حساب واقعی | backoff |

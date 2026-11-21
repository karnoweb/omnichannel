# مرجع سرویس‌دهنده — Instagram Platform (`instagram`)

**تاریخ پژوهش:** 2026-11-21 | **نسخه پکیج:** 13.3.0 | **وضعیت مرحله:** تکمیل جزئی با محدودیت (در صورت ذکر شکاف رسمی)

## ۶.۱ شناسنامه و منابع رسمی

- **نام رسمی:** Instagram Platform
- **سازمان:** Meta Platforms, Inc.
- **کاربرد در Omnichannel:** انتشار/کاتالوگ/پیام‌رسانی بسته به درایور
- **پورتال‌ها:** https://developers.facebook.com/docs/instagram-platform/ — Host رسمی Instagram Login: `graph.instagram.com`؛ Facebook Login: `graph.facebook.com`
- **منابع اصلی:** IG-S001, IG-S002

## ۶.۲ راه‌اندازی و احراز هویت

جزئیات مقایسه‌ای در [AUTHENTICATION.md](AUTHENTICATION.md). خلاصه این سرویس در جدول عملیات و منابع بالا.

## ۶.۳ اطلس قابلیت‌ها و انواع API

| شناسه قابلیت | خانواده API | قابلیت | شرح فنی | حساب/مجوز | وضعیت رسمی | Omnichannel | منابع |
|---|---|---|---|---|---|---|---|
| IG-CAP-001 | Content Publishing | ایجاد ظرف رسانه | POST `/<IG_ID>/media` | Professional + token | رسمی ـ مشروط | Post/Carousel/Reel/Story | IG-S001 |
| IG-CAP-002 | Content Publishing | انتشار نهایی | POST `/<IG_ID>/media_publish` | همان | رسمی ـ عمومی | publish در InstagramDriver | IG-S001 |
| IG-CAP-003 | Content Publishing | سهمیه انتشار | GET `content_publishing_limit` | همان | رسمی ـ عمومی | InstagramApiClient::contentPublishingLimit | IG-S001 |
| IG-CAP-004 | Insights | آمار و کامنت | خارج از دامنه فعلی پکیج | — | رسمی ـ مشروط | خارج از دامنه | IG-S001 |

## ۶.۴ مرجع دقیق عملیات API

| شناسه عملیات | هدف | روش | آدرس پایه | مسیر/نسخه | احراز/مجوز | ورودی/خروجی | منبع | وضعیت |
|---|---|---|---|---|---|---|---|---|
| IG-OP-001 | ایجاد container | POST | graph.instagram.com | /{version}/{ig_user_id}/media | Bearer / query access_token | image_url|video_url، media_type | IG-S001 | full_official |
| IG-OP-002 | انتشار | POST | graph.instagram.com | /{version}/{ig_user_id}/media_publish | Bearer | creation_id | IG-S001 | full_official |
| IG-OP-003 | وضعیت container | GET | graph.instagram.com | /{version}/{container_id}?fields=status_code | Bearer | status_code | IG-S001 | full_official |
| IG-OP-004 | سهمیه ۲۴h | GET | graph.instagram.com | /{version}/{ig_user_id}/content_publishing_limit | Bearer | quota_usage | IG-S001 | full_official |

## ۶.۵ رسانه و محتوا

| قابلیت | محدودیت رسمی | منبع | پکیج (`config`) |
|---|---|---|---|
| تصویر | JPEG؛ URL عمومی | IG-S001 | jpeg، 8MiB |
| Carousel | حداکثر ۲۰ آیتم | IG-S001 | max 10 children |
| Rate | ۱۰۰ پست API در ۲۴h روی `media_publish` | IG-S001 | rate_limit 25/h internal |

## ۶.۶ محدودیت نرخ، خطا و قابلیت اطمینان

- سهمیه ۱۰۰ پست/۲۴ ساعت روی endpoint انتشار [IG-S001].
- **P42-DIFF-001:** در همان سند، بخش Carousel سقف «۵۰ پست منتشرشده در ۲۴ ساعت» ذکر می‌کند — تفاوت با سقف کلی ۱۰۰ نیازمند تفسیر محصولی/تأیید Meta است.
- HTTP Graph errors + `status_code` داخلی container.

## ۶.۷ رخدادها و همگام‌سازی

وب‌هوک Instagram Platform مستند است؛ پکیج فعلی polling container دارد، webhook ندارد.

## ۶.۸ هزینه، شرایط بهره‌برداری و امنیت عملیاتی

PPA، Advanced Access، شرایط Meta Business — IG-S001. هزینه API انتشار جدا از تبلیغات.

## ۶.۹ وضعیت فعلی پکیج و اختلاف‌های مستند

| شناسه | رفتار رسمی | کد فعلی | تست ممیزی ۴.۱ | اختلاف | شاهد |
|---|---|---|---|---|---|
| IG-CAP-002 | graph.instagram.com + نسخه LATEST در مثال‌ها (مثلاً v27.0) | `graph_url` پیش‌فرض graph.instagram.com؛ `api_version` v26.0 | DriverContract — mock | P42-DIFF-002 نسخه | IG-S001 + config |
| IG-OP-004 | quota از content_publishing_limit | متد contentPublishingLimit پیاده | بله mock | — | InstagramApiClient |

کلاینت: `src/Channels/Instagram/InstagramApiClient.php` — مسیر `{graph_url}/{api_version}/{id}`.

## ۶.۱۰ سناریوهای اعتبارسنجی آینده و پرسش‌های باز

| شناسه سناریو | قابلیت | محیط | پیش‌نیاز | انتظار | جانبی/پاکسازی | وضعیت |
|---|---|---|---|---|---|---|
| IG-VAL-001 | انتشار تصویر | حساب IG Professional + app Meta | OAuth scopes پکیج | media_id | حذف دستی پست | برنامه‌ریزی‌شده |
| IG-VAL-002 | سهمیه | همان | — | quota_usage | — | برنامه‌ریزی‌شده |

### پرسش‌های باز

| شناسه | اولویت | پرسش | دلیل عدم قطعیت | اثر |
|---|---|---|---|---|
| P42-Q-003 | P1 | سقف ۵۰ vs ۱۰۰ در doc carousel | دو پاراگراف متفاوت در IG-S001 | برنامه‌ریزی نرخ |

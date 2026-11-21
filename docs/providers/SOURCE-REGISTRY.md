# فهرست منابع رسمی (Phase 4.2)

**تاریخ مشاهده پیش‌فرض:** 2026-11-21  
**نسخه پکیج مبنا:** 13.3.0  
**قاعده:** تاریخ انتشار منبع در صورت نامشخص بودن در صفحه، «نامشخص» ثبت شده است.

| شناسه | سرویس | عنوان | URL | مالک | نسخه/تاریخ انتشار | مشاهده | دسترسی | ادعاهای پشتیبانی‌شده |
|---|---|---|---|---|---|---|---|---|
| IG-S001 | Instagram | Content Publishing | https://developers.facebook.com/docs/instagram-platform/content-publishing/ | Meta | نامشخص | 2026-11-21 | باز | media، media_publish، content_publishing_limit |
| IG-S002 | Instagram | Instagram API with Instagram Login | https://developers.facebook.com/docs/instagram-platform/instagram-api-with-instagram-login/ | Meta | نامشخص | 2026-11-21 | باز | OAuth، scopes، graph.instagram.com |
| TG-S001 | Telegram | Bot API (sendMessage و مرجع کلی) | https://core.telegram.org/bots/api | Telegram | نامشخص | 2026-11-21 | باز | sendMessage، sendMediaGroup، getMe |
| TG-S002 | Telegram | Making requests | https://core.telegram.org/bots/api#making-requests | Telegram | نامشخص | 2026-11-21 | باز | `https://api.telegram.org/bot<token>/<method>` |
| WA-S001 | WhatsApp/Meta | Catalog batch / Graph | https://developers.facebook.com/docs/graph-api/reference/catalog/items_batch/ | Meta | Graph v26+ | 2026-11-21 | باز (ورود برای جزئیات) | items_batch، handle |
| WA-S002 | WhatsApp/Meta | check_batch_request_status | https://developers.facebook.com/docs/graph-api/reference/catalog/check_batch_request_status/ | Meta | نامشخص | 2026-11-21 | باز | وضعیت batch |
| WA-S003 | WhatsApp/Meta | Catalog products edge | https://developers.facebook.com/docs/marketing-api/catalog/reference/products/ | Meta | نامشخص | 2026-11-21 | باز | فیلتر retailer_id |
| BS-S001 | Basalam | SalamAPI Docs | https://developers.basalam.com/docs | Basalam | نامشخص | 2026-11-21 | باز | OAuth، شروع سریع |
| BS-S002 | Basalam | OpenAPI host | https://openapi.basalam.com | Basalam | نامشخص | 2026-11-21 | باز | REST v1 |
| BS-S003 | Basalam | Auth host | https://auth.basalam.com | Basalam | نامشخص | 2026-11-21 | باز | OAuth2 |
| BL-S001 | Bale | مستندات بازوی بله | https://docs.bale.ai/ | پیام‌رسان بله | نامشخص | 2026-11-21 | باز | tapi.bale.ai، sendMessage |
| RB-S001 | Rubika | Bot API (شاهد کد + جست‌وجوی محدود) | https://botapi.rubika.ir/ | Rubika | v3 | 2026-11-21 | محدود | JSON POST `v3/{token}/{method}` |
| GP-S001 | Gap | API گپ (عمومی محدود) | https://gap.im/developers | Gap | نامشخص | 2026-11-21 | جزئی/مسدود | `api.gap.im/{method}` از کد |
| ET-S001 | Eitaa/Eitaayar | درگاه ایتایار | https://eitaayar.ir | شخص ثالث | نامشخص | 2026-11-21 | جزئی | `eitaayar.ir/api/{token}/{method}` |

**منابع داخلی (غیررسمی ولی معتبر برای کد):**

| شناسه | مسیر |
|---|---|
| INT-S001 | `docs/phase-04/step-01/DRIVER-AUDIT.md` |
| INT-S002 | `config/omnichannel.php` (خوانده‌شده، تغییر نداده‌شده) |
| INT-S003 | `src/Channels/*ApiClient.php` |

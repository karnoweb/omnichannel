# DRIVER-AUDIT — استخراج از کد

**وضعیت اعتبار رسمی:** همه URLها «موجود در config/کد»؛ اعتبار رسمی API تا مرحله ۴.۲ **تأییدنشده**.

## جدول آدرس پایه

| درایور | شناسه config | base / graph (پیش‌فرض) | کلاس اصلی |
|---|---|---|---|
| Demo | demo | `https://demo.local` | `Channels\Demo\DemoDriver` |
| Instagram | instagram | `https://graph.instagram.com` (+ OAuth URLs) | `InstagramDriver` |
| Basalam | basalam | `https://openapi.basalam.com` | `BasalamDriver` |
| Telegram | telegram | `https://api.telegram.org` | `TelegramDriver` |
| Bale | bale | `https://tapi.bale.ai` | `BaleDriver` |
| Rubika | rubika | `https://botapi.rubika.ir/v3` | `RubikaDriver` |
| Gap | gap | `https://api.gap.im` | `GapDriver` |
| Eitaa | eitaa | `https://eitaayar.ir` | `EitaaDriver` |
| WhatsApp Catalog | whatsapp | `https://graph.facebook.com` | `WhatsAppDriver` |

منبع پیش‌فرض URL: `config/omnichannel.php` بخش `channels.*`.

## زیرساخت مشترک

| مسیر | نقش |
|---|---|
| `Channels/Messaging/BotApiClient.php` | `{baseUrl}/bot{token}/{method}` برای Telegram/Bale الگو |
| `Http/BaseApiClient.php` | GET/POST/PATCH/DELETE + rate limit hook |
| `ValueObjects/ChannelCapabilities.php` | اعلام Capability |

## الگوی جدول عملیات (خلاصه)

| درایور | Product | Post/Media | حذف/غیرفعال | توکن | تست اختصاصی |
|---|---|---|---|---|---|
| Demo | بله | بله | محدود | ندارد | via Feature mocks |
| Instagram | خیر | Carousel/Reel/Story/Post | deactivate | OAuth refresh | DriverContract + Feature |
| Telegram/Bale | خیر | پیام/آلبوم | edit/delete | bot token | Feature |
| Basalam | بله | بله | deactivate | OAuth | Feature جزئی |
| WhatsApp | Catalog product | خیر feed | deactivate | Graph token | `WhatsAppCatalogBatchOutcomeTest` |
| Rubika/Gap/Eitaa | محدود | پیام‌رسان | varies | token در credentials | کم |

## نکات اختصاصی

### WhatsApp Catalog

- پیام عمومی و Status **خارج از دامنه** — `WhatsAppDriver::capabilities` reasons.
- `whatsapp_catalog.auto_reconcile` پیش‌فرض **false** — `config/omnichannel.php`.
- `PollWhatsAppCatalogBatchJob`, `InspectWhatsAppCatalogBatchCommand`.

### Basalam

- `BasalamApiClient` — categories/products (مسیرها در کلاینت).
- `BasalamCategoryMapper` + `CategoryMappingResolver` (فاز ۳).
- `SyncMarketplaceTaxonomyCommand` — نیاز اتصال و API.

### Demo

- شبیه‌سازی نرخ خطا/تأخیر از config؛ **سرویس واقعی نیست**.

## پرسش‌های مرحله ۴.۲ (نمونه)

- Instagram Graph نسخه `v26.0` — هنوز معتبر؟
- Basalam OpenAPI مسیر taxonomy/attributes رسمی
- WhatsApp Catalog batch lifecycle و محدودیت نرخ
- Gap/Eitaa/Rubika — تفاوت Bot API با Telegram

جزئیات endpoint از خواندن `*ApiClient.php` هر درایور در ممیزی بعدی عمیق‌تر تکمیل شود (این سند اسکلت استخراج config + ساختار).
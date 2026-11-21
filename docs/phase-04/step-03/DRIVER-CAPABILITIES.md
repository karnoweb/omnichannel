# DRIVER-CAPABILITIES — شاخص درایورها

مرحله ۴.۳ | نسخه 13.3.0

هر سرویس در فایل جدا مستند شده است. جداول وضعیت + شواهد طبق §۶.۲ پرامپت ۴.۳.

| درایور | سرویس | مسیر سورس | فایل ماتریس | خلاصه |
|---|---|---|---|---|
| `instagram` | Instagram Graph | `src/Channels/Instagram/` | [drivers/instagram.md](drivers/instagram.md) | انتشار رسانه اجتماعی؛ حذف با Instagram Login محدود؛ نسخه Graph و نرخ P42-DIFF |
| `telegram` | Bot API | `src/Channels/Telegram/`, `Messaging/` | [drivers/telegram.md](drivers/telegram.md) | پیام‌رسان کامل؛ آلبوم، پین، thumbnail؛ مرجع رسمی قوی |
| `whatsapp` | Catalog API | `src/Channels/WhatsApp/` | [drivers/whatsapp-catalog.md](drivers/whatsapp-catalog.md) | فقط کاتالوگ؛ batch محصول؛ reconcile اختیاری |
| `basalam` | Open API v1 | `src/Channels/Basalam/` | [drivers/basalam.md](drivers/basalam.md) | marketplace؛ deactivate به‌جای delete؛ status IDs |
| `bale` | Bale Bot | `src/Channels/Bale/`, `Messaging/` | [drivers/bale.md](drivers/bale.md) | مشابه TG با API مستقل؛ اشتراک `BotApiClient` |
| `rubika` | Bot v3 | `src/Channels/Rubika/` | [drivers/rubika.md](drivers/rubika.md) | پیام‌رسان؛ مستند رسمی ضعیف |
| `gap` | Gap | `src/Channels/Gap/` | [drivers/gap.md](drivers/gap.md) | token header؛ مستند جزئی |
| `eitaa` | Eitaayar | `src/Channels/Eitaa/` | [drivers/eitaa.md](drivers/eitaa.md) | درگاه ثالث؛ ریسک وابستگی |
| `demo` | داخلی | `src/Channels/Demo/` | [drivers/demo.md](drivers/demo.md) | تست قرارداد؛ بدون API خارجی |

مرجع رسمی تجمیعی: [../../providers/README.md](../../providers/README.md)
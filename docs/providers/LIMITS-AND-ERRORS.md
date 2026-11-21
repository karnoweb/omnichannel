# محدودیت‌ها و خطا (مقایسه‌ای)

**تفکیک:** سقف‌های `config/omnichannel.php` سیاست داخلی صف‌اند، نه سهمیه رسمی API.

| سرویس | محدودیت رسمی (شاهد) | داخلی پکیج | خطای شاخص |
|---|---|---|---|
| Instagram | 100 posts/24h API publish [IG-S001] | 25/h | Graph error + container status |
| Instagram | Carousel section: 50/24h [IG-S001] | carousel max 10 | P42-DIFF-001 |
| Telegram | 429 rate [TG-S001] | 20/min | error_code |
| WhatsApp | catalog rate [WA-S001] | 60/h | batch handle |
| Basalam | پورتال | 60/min | HTTP + body |
| Bale | docs.bale.ai | 20/min | ok=false |
| Rubika/Gap/Eitaa | نامشخص | 20/min | mapper اختصاصی |

# API-CONTRACT-COMPARISON

تطبیق عملیات فراخوانی‌شده در سورس با مرجع [providers/API-ENDPOINTS.md](../../providers/API-ENDPOINTS.md). عملیات رسمی بدون کد: «وجود ندارد در محدوده بررسی».

| درایور | OP | روش/مسیر پکیج | رسمی | مجوز/کلید | تفاوت | تطابق | شکاف |
|---|---|---|---|---|---|---|---|
| instagram | IG-OP-001 | POST `{graph}/v26.0/{id}/media` | v26/v27 Graph | Bearer | نسخه پیش‌فرض 26 | جزئی | P42-DIFF-002 |
| instagram | IG-OP-002 | POST `media_publish` | همان | Bearer | سقف نرخ داخلی 25/h | جزئی | P42-DIFF-001 |
| telegram | TG-OP-002 | `api.telegram.org/bot…/sendMessage` | Bot API | path token | — | مطابق | — |
| telegram | TG-OP-003 | `sendMediaGroup` | Bot API | path | — | مطابق | — |
| whatsapp | WA-OP-001 | `items_batch` v26.0 | Catalog | Bearer | batch async | مطابق | — |
| whatsapp | WA-OP-002 | `check_batch_request_status` | Catalog | Bearer | — | مطابق | — |
| basalam | BS-OP-001 | GET users/me v1 | OpenAPI | OAuth | — | مطابق | — |
| basalam | BS-OP-002 | POST products | OpenAPI | Bearer | status از env | نامشخص | P42-DIFF-003 |
| bale | BL-OP-001 | `tapi.bale.ai/bot…` | Bale docs | path | کلاینت مشترک TG | مشروط | P42-DIFF-005 |
| rubika | RB-OP-002 | `botapi.rubika.ir/v3/…` | portal محدود | JSON+path | مستند ناقص | نامشخص | P42-DIFF-006 |
| gap | GP-OP-001 | `api.gap.im/sendMessage` | جزئی | header token | — | نامشخص | P42-DIFF-007 |
| eitaa | ET-OP-001 | `eitaayar.ir/api/…` | درگاه | path | API مستقیم ایتا نامشخص | نامشخص | P42-DIFF-008 |

## عملیات رسمی مرتبط بدون پیاده‌سازی (نمونه)

| سرویس | عملیات | وضعیت |
|---|---|---|
| Instagram | حذف media با Instagram Login | عمداً unsupported در درایور |
| Basalam | DELETE محصول | وجود ندارد در محدوده بررسی — deactivate |
| WhatsApp | پیام‌رسانی 24h window | خارج دامنه کانال catalog |
| همه | Webhook یکپارچه | وجود ندارد در محدوده بررسی |
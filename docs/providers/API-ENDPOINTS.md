# فهرست Endpointها (قابل جست‌وجو)

**مشاهده:** 2026-11-21 | برچسب `پکیج` = مقدار پیاده‌سازی فعلی.

| شناسه | سرویس | روش | مسیر/URL | نسخه | Auth | منبع | وضعیت | پکیج |
|---|---|---|---|---|---|---|---|---|
| IG-OP-001 | instagram | POST | `{graph}/{ver}/{ig_id}/media` | v26.0 پکیج | Bearer | IG-S001 | رسمی | InstagramApiClient |
| IG-OP-002 | instagram | POST | `.../media_publish` | v26.0 | Bearer | IG-S001 | رسمی | همان |
| TG-OP-002 | telegram | POST | `api.telegram.org/bot<token>/sendMessage` | — | path | TG-S001 | رسمی | BotApiClient |
| TG-OP-003 | telegram | POST | `.../sendMediaGroup` | — | path | TG-S001 | رسمی | همان |
| WA-OP-001 | whatsapp | POST | `graph.facebook.com/{ver}/{catalog_id}/items_batch` | v26.0 | Bearer | WA-S001 | رسمی | WhatsAppCatalogClient |
| WA-OP-002 | whatsapp | GET | `.../check_batch_request_status` | v26.0 | Bearer | WA-S002 | رسمی | همان |
| WA-OP-003 | whatsapp | GET | `.../products?filter=retailer_id` | v26.0 | Bearer | WA-S003 | رسمی | همان |
| BS-OP-001 | basalam | GET | `openapi.basalam.com/v1/users/me` | v1 | Bearer | BS-S001 | رسمی | BasalamApiClient |
| BS-OP-002 | basalam | POST | `.../v1/vendors/{id}/products` | v1 | Bearer | BS-S001 | رسمی | همان |
| BS-OP-003 | basalam | POST | `.../v1/files` | v1 | Bearer | BS-S001 | رسمی | همان |
| BL-OP-001 | bale | POST | `tapi.bale.ai/bot<token>/sendMessage` | — | path | BL-S001 | رسمی | BotApiClient |
| RB-OP-002 | rubika | POST | `botapi.rubika.ir/v3/<token>/sendMessage` | v3 | path+JSON | RB-S001 | کد | RubikaApiClient |
| GP-OP-001 | gap | POST | `api.gap.im/sendMessage` | — | header token | GP-S001 | جزئی | GapApiClient |
| ET-OP-001 | eitaa | POST | `eitaayar.ir/api/<token>/sendMessage` | — | path | ET-S001 | درگاه | EitaaApiClient |

# PUBLIC-CONTRACTS-AND-HOST

مرجع تفصیل API: [step-01/PUBLIC-API-INVENTORY.md](../step-01/PUBLIC-API-INVENTORY.md).

## سطح عمومی پکیج

| مصرف‌کننده | ورودی | خروجی / خطا |
|---|---|---|
| میزبان | `Omnichannel` facade / services | `Publication`, events |
| Job worker | queue jobs | state transition |
| Artisan | commands | diagnostics |

## قراردادهای میزبان (الزامی / اختیاری)

| قرارداد | نقش | فاز | الزام |
|---|---|---|---|
| `ProductRepository` | داده محصول | انتشار Product | برای product source |
| `BranchScopedProductRepository` | ایزوله شعبه | commerce | توصیه |
| `PublicationMediaStorageContract` | بایت رسانه | `omc-asset` | اگر غیر local default |
| `PublicationMediaUrlResolverContract` | URL (legacy) | سازگاری | اختیاری |
| `PublicationSourceResolverContract` | Reference source | FR-039 | registrar میزبان |
| `InternalCategoryResolverContract` | UI mapping | taxonomy | اختیاری |
| `CatalogSyncTriggerContract` | رویداد قیمت/موجودی | sync | اگر sync on |
| `BranchContextResolverContract` | شعبه | همه عملیات | امنیت |
| `ActorContextResolverContract` | actor log | audit | توصیه |

نمونه: [HOST-INTEGRATION-PHASE-03.md](../../HOST-INTEGRATION-PHASE-03.md), `docs/examples/host/`.

## سازگاری نسخه

- کلیدهای config با fallback در `config/omnichannel.php` — breaking تغییر باید در 4.6 مستند شود.
- enum `Capability` — افزودن case جدید backward compatible.
- migration squashed — مسیر ارتقا در `MIGRATION-AND-COMPATIBILITY.md`.

## To-Be پیشنهادی

- سند واحد «Host integration guide» (۴.۸) بدون تغییر قرارداد.
- نمونه registrar Reference در host examples.
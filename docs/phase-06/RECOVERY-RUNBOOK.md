# راهنمای بازیابی catalog sync

## اجرای دوره‌ای (میزبان)

1. `php artisan omnichannel:doctor --json` — شمارش `pending` / `failed` / `unknown`.
2. در صورت فعال بودن sync: فراخوانی `CatalogSyncRecoveryService::recoverStale()` از زمان‌بند میزبان (نه scheduler پنهان پکیج).
3. برای واتس‌اپ `unknown`: `omnichannel:inspect-whatsapp-catalog-batch` سپس `finalizeWhenReady` در صورت فعال بودن `whatsapp_catalog.auto_reconcile`.

## ردیف‌های legacy مشکوک

| نشانه | اقدام پیشنهادی |
|---|---|
| `synced` بدون `applied_snapshot` | snapshot میزبان را دوباره `recordChange` کنید |
| `synced` بدون `last_synced_at` | همانند بالا یا علامت‌گذاری `failed` دستی |
| `dispatching` قدیمی | `recoverStale` |

## محدودیت‌ها

- `attempts` >= `omnichannel.queue.tries` دیگر دوباره dispatch نمی‌شود.
- sync خاموش = `disabled` و صفر درخواست HTTP.
# نگاشت فیلدهای کاتالوگ

## میزبان → `CatalogSyncState`

| فیلد snapshot | ذخیره | ملاحظه |
|---|---|---|
| `price` | `metadata.desired_snapshot.price` + `price_hash` | integer/string از میزبان |
| `stock` | `metadata.desired_snapshot.stock` + `stock_hash` | `0` مجاز |
| `currency` | `metadata.desired_snapshot.currency` | برای WA در `price` رشته قیمت |

## Basalam `update`

| snapshot | فیلد API |
|---|---|
| price | `primary_price` |
| stock | `stock` |

`deactivate` → `status` = `config('omnichannel.channels.basalam.product_status.draft')` (پیش‌فرض env: 3790). حذف API پشتیبانی نمی‌شود؛ deactivate حذف نیست.

## WhatsApp catalog `update`

| snapshot | فیلد catalog |
|---|---|
| price + currency | `price` (فرمت شده) |
| stock | `inventory`, `availability` |
| product_id | `retailer_id` از `sku-{product_id}` یا `whatsapp.retailer_id` |
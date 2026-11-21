# WhatsApp Catalog — ماتریس تفصیلی

[providers/whatsapp-catalog.md](../../providers/whatsapp-catalog.md) | `src/Channels/WhatsApp/`

## جدول وضعیت

| P43-ROW | OMC-CAP | قابلیت | رسمی | اعلام | پیاده‌سازی | دسترسی |
|---|---|---|---|---|---|---|
| P43-ROW-005 | OMC-CAP-007 | items_batch ایجاد/به‌روز | مشروط Commerce | اعلام | کامل | عمومی |
| P43-ROW-019 | OMC-CAP-008 | قیمت/موجودی | مشروط | اعلام | کامل | عمومی |
| P43-ROW-020 | OMC-CAP-011 | deactivate محصول | مشروط | اعلام | کامل | عمومی |
| P43-ROW-021 | OMC-CAP-002 | پیام واتساپ | عمومی ولی خارج کانال | **ند** | ند | خارج دامنه |

## شواهد

| P43-ROW | OP | کد | تست |
|---|---|---|---|
| P43-ROW-005 | WA-OP-001 | `WhatsAppCatalogClient`, `WhatsAppDriver` | WhatsApp catalog tests |
| P43-ROW-019 | batch fields | همان | Price/stock tests |
| reconcile | — | `WhatsAppCatalogReconciliationService` | feature tests، پیش‌فرض off |

## جمع‌بندی

دامنه = کاتالوگ نه پیام‌رسانی. batch status و reconcile نیاز تصمیم عملیاتی (P42-Q-005).
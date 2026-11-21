# Bale — ماتریس تفصیلی

[providers/bale.md](../../providers/bale.md) | `src/Channels/Bale/`, `BotApiDriver`

## جدول وضعیت

| P43-ROW | OMC-CAP | قابلیت | رسمی | پیاده‌سازی | تطابق |
|---|---|---|---|---|---|
| P43-ROW-008 | OMC-CAP-002 | پیام | عمومی BL-S001 | کامل | مشروط¹ |
| P43-ROW-025 | OMC-CAP-005 | آلبوم | اعلام | کامل | مشروط¹ |
| P43-ROW-026 | OMC-CAP-010 | حذف | 48h window در limits | کامل | P42-DIFF-004 caption |

¹ اشتراک `BotApiClient` با Telegram — P42-DIFF-005.

## شواهد

- `BaleDriver::capabilities()` خط 29+
- `BL-OP-001` در [API-ENDPOINTS](../../providers/API-ENDPOINTS.md)
- تست: `DriverContractTest`

## جمع‌بندی

عملکرد نزدیک Telegram؛ تفکیت قراردادی و سقف کپشن نیاز شفاف‌سازی (P42-Q-009).
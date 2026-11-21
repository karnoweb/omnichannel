# Rubika — ماتریس تفصیلی

[providers/rubika.md](../../providers/rubika.md) | `src/Channels/Rubika/`

## جدول وضعیت

| P43-ROW | OMC-CAP | قابلیت | رسمی | پیاده‌سازی |
|---|---|---|---|---|
| P43-ROW-009 | OMC-CAP-002 | sendMessage | **نامشخص** RB-S001 | کامل در کد |
| P43-ROW-027 | OMC-CAP-009 | ویرایش | نامشخص | جزئی/محدود |
| P43-ROW-028 | OMC-CAP-005 | آلبوم | پشتیبانی نمی‌شود | ند |

## شواهد

- `RubikaApiClient` — `RB-OP-002`
- `RubikaDriver::capabilities()` — بدون Album
- تست: `DriverContractTest` (mock HTTP)

## جمع‌بندی

پیاده‌سازی از کد/پورتال محدود؛ شکاف شواهد رسمی (P42-DIFF-006, P43-GAP-010).
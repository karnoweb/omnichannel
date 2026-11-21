# Telegram — ماتریس تفصیلی

[providers/telegram.md](../../providers/telegram.md) | `src/Channels/Telegram/`, `BotApiDriver`

## جدول وضعیت

| P43-ROW | OMC-CAP | قابلیت | رسمی | اعلام | پیاده‌سازی | دسترسی | تطابق |
|---|---|---|---|---|---|---|---|
| P43-ROW-003 | OMC-CAP-002 | sendMessage | عمومی | اعلام | کامل | عمومی | مطابق |
| P43-ROW-004 | OMC-CAP-005 | sendMediaGroup | عمومی | اعلام | کامل | عمومی | مطابق |
| P43-ROW-016 | OMC-CAP-009 | editMessage | عمومی | اعلام | کامل | عمومی | مطابق |
| P43-ROW-017 | OMC-CAP-010 | deleteMessage | عمومی | اعلام | کامل | عمومی | مطابق |
| P43-ROW-018 | OMC-CAP-017 | pin | عمومی | اعلام | کامل | عمومی | مطابق |

## شواهد

| P43-ROW | OP | کد | تست |
|---|---|---|---|
| P43-ROW-003 | TG-OP-002 | `BotApiClient` | `DriverContractTest`, Telegram tests |
| P43-ROW-004 | TG-OP-003 | همان | MediaGroup tests |

## جمع‌بندی

مرجع Bot API پایدار؛ الگوی مرجع برای `Messaging`. زمان‌بندی بومی ندارد → OMC-CAP-012 هسته.
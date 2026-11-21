# DRIVER-CONTRACT-MATRIX (خلاصه گام ۵.۱)

| درایور | publish | update | delete | deactivate | product | scheduling | یادداشت |
|--------|---------|--------|--------|------------|---------|------------|---------|
| instagram | بله | بله | خیر* | — | خیر | بله | *بدون delete در capabilities |
| telegram | بله | بله | بله | — | خیر | بله | BotApi |
| bale | بله | بله | بله | — | خیر | بله | بدون parse_mode |
| whatsapp | کاتالوگ | بله | بله | بله | بله | بله | catalog-only |
| basalam | بله | بله | خیر* | بله | بله | بله | *draft deactivate |
| rubika | بله | بله | بله | — | خیر | بله | best-effort doc |
| gap | بله | بله | بله | — | خیر | بله | best-effort doc |
| eitaa | بله | خیر | خیر | — | خیر | بله+native | DEC-002 |
| demo | بله | بله | بله | — | بله | بله | آزمایشی |

جزئیات قابلیت: متد `capabilities()` هر درایور در `src/Channels/*/`.

تست قرارداد: `tests/Drivers/DriverContractTest.php` — تکمیل در ۵.۸ (WP-05-07).
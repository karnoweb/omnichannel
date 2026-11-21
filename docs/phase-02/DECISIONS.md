# Phase 02 decisions

| ID | Decision |
| --- | --- |
| D02-01 | Keep `inherit_global` deprecated; strict branch context only. |
| D02-02 | SQLite-only DB verification for this iteration. |
| D02-03 | Separate job throttle vs per-HTTP `ApiRequestRateLimiter`. |
| D02-04 | WhatsApp auto batch finalization behind `whatsapp_catalog.auto_reconcile` default **false**. |
| D02-05 | Host `karno-base` unchanged; integration report-only. |
| D02-06 | Internal rate limits are not marketed as official platform quotas. |
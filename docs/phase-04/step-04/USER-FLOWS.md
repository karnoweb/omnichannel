# USER-FLOWS

برچسب: **موجود** = رفتار پیاده‌شده 13.3.0؛ **مقصد** = انتظار محصولی پس از تکمیل شکاف‌ها.

## FLOW-001 — پیکربندی اتصال کانال

| | |
|---|---|
| بازیگر | توسعه‌دهنده میزبان |
| پیش‌شرط | پکیج نصب، migration |
| مسیر موجود | ایجاد Connection + credentials در DB میزبان → فراخوانی Publishing |
| خطا | توکن نامعتبر → exception درایور |
| FR | FR-001..003 |
| وضعیت | موجود |

## FLOW-002 — انتشار تک‌کاناله FreeForm

| | |
|---|---|
| پیش‌شرط | Connection فعال، capabilities شامل Post/Message |
| موجود | Create publication → queue/job → driver publish → state |
| خطا | unsupported capability → `UnsupportedCapabilityException` |
| FR | FR-006, FR-008, FR-030 |

## FLOW-003 — انتشار محصول به بازارگاه

| | |
|---|---|
| پیش‌شرط | ProductRepository bound، mapping دسته |
| موجود | Product data → Basalam/WA driver |
| خطا | mapping ناقص → validation/error |
| FR | FR-007, FR-021, FR-022 |

## FLOW-004 — انتشار گروهی Batch

| | |
|---|---|
| موجود | `PublicationBatchService::create` چند target |
| نتیجه جزئی | وضعیت per-publication |
| مقصد | retry امن مقصد شکست (FR-015) |
| FR | FR-014..016 |

## FLOW-005 — زمان‌بندی

| | |
|---|---|
| موجود | scheduled_at → dispatch command |
| FR | FR-009 |

## FLOW-006 — رسانه omc-asset

| | |
|---|---|
| پیش‌شرط | `PublicationMediaStorageContract` در میزبان |
| موجود | resolve bytes → آپلود/attach درایور |
| خطا | asset ناموجود |
| FR | FR-017..020 |

## FLOW-007 — catalog sync (اختیاری)

| | |
|---|---|
| پیش‌شرط | enabled global + connection + host trigger |
| موجود | job debounce؛ default off |
| مقصد | سیاست روشن پس از wire میزبان |
| FR | FR-026..028 |

## FLOW-008 — خطا و بازیابی عملیاتی

| | |
|---|---|
| موجود | failed state publication؛ queue retry میزبان |
| مقصد | reconcile WA batch نامشخص |
| FR | FR-032..034 |

## FLOW-009 — ارتقا پکیج

| | |
|---|---|
| موجود | migrations جدید |
| مقصد | راهنمای ارتقا + تست 13.2→13.3 |
| FR | FR-038 |

## FLOW-010 — توسعه درایور جدید

| | |
|---|---|
| موجود | Demo نمونه |
| FR | FR-037 |
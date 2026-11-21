# PRODUCT-DECISIONS — مرحله ۴.۴

مرجع قبلی (بدون تغییر): [step-03/PRODUCT-DECISIONS.md](../step-03/PRODUCT-DECISIONS.md)

## DEC-001 — سقف انتشار Instagram

| فیلد | مقدار |
|---|---|
| منشأ | P43-DEC-001, P42-DIFF-001 |
| وضعیت | باز — پیشنهاد تحلیلگر: mirror Meta با سقف امن |
| گزینه‌ها | A داخلی B mirror C per-tenant |
| اثر | FR-031, نرخ انتشار اپراتور |
| نیازمندی | FR-031 |
| مرحله بعد | ۴.۵ config policy |

## DEC-002 — Eitaa و درگاه

| منشأ | P43-DEC-002 |
| وضعیت | باز — پیشنهاد: API رسمی تا شواهد؛ وگرنه بررسی deprecate |
| اثر | سطح C→B یا حذف |
| ریسک | RISK-012 |

## DEC-003 — catalog_sync پیش‌فرض

| منشأ | P43-DEC-003, P-05 |
| وضعیت | پیشنهاد تحلیلگر: **off** (هم‌راستا فاز ۳) — منتظر مصوب مالک |
| اثر | FR-026 |

## DEC-004 — WhatsApp messaging

| منشأ | P43-DEC-004 |
| وضعیت | پیشنهاد: catalog only در دامنه فعلی |
| FR | FR-042 |

## DEC-005 — Rubika/Gap پشتیبانی

| منشأ | P43-DEC-005 |
| وضعیت | باز — پیشنهاد: best-effort تا doc رسمی |
| FR | FR-004, PROVIDER |

## DEC-006 — Graph API version

| منشأ | P43-DEC-006 |
| وضعیت | باز |
| اثر | DOC-002, درایور IG |

## DEC-007 — Illuminate 13 میزبان

| منشأ | P43-DEC-007, OMC-AUD-002 |
| وضعیت | باز — پیشنهاد: سند الزام میزبان |
| NFR | NFR-014,015 |

## DEC-008 — Webhook (جدید)

| پرسش | آیا دریافت رخداد در دامنه است؟ |
| وضعیت | باز |
| پیشنهاد | تعویق؛ FR-040 |
| اثر | معماری ۴.۵ |
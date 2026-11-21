# ARCHITECTURE-DECISIONS (ADR)

وضعیت: پیشنهاد معمار مگر «حفظ As-Is» ذکر شود.

## ADR-001 — Publication واحد وضعیت

- **وضعیت:** پذیرفته (As-Is)
- **شاهد:** phase-03 DECISIONS, P-03
- **FR:** FR-014

## ADR-002 — omc-asset via contract

- **وضعیت:** پذیرفته
- **شاهد:** `PublicationMediaStorageContract`

## ADR-003 — catalog sync opt-in

- **وضعیت:** پذیرفته
- **P-05**, `catalog_sync.enabled=false`

## ADR-004 — بدون وابستگی karno-base

- **وضعیت:** پذیرفته
- **تست:** `NoHostDependencyTest`

## ADR-005 — BotApiClient مشترک TG/Bale

- **وضعیت:** باز / مشروط
- **ریسک:** P43-GAP-005
- **To-Be:** parity audit یا کلاینت Bale جدا — منتظر DEC

## ADR-006 — Graph API v26

- **وضعیت:** باز
- **DEC-006** — bump با تست regression

## ADR-007 — Instagram rate policy

- **وضعیت:** باز — DEC-001

## ADR-008 — Webhook

- **وضعیت:** رد موقت / خارج دامنه — DEC-008

## ADR-009 — Eitaa gateway

- **وضعیت:** باز — DEC-002

پرسش‌های مالک: [step-04/PRODUCT-DECISIONS.md](../step-04/PRODUCT-DECISIONS.md).
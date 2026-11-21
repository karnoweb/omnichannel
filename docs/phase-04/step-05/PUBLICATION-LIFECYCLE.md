# PUBLICATION-LIFECYCLE

## ماشین وضعیت (As-Is)

`PublicationStatus` — شواهد: `PublicationStateMachineTest`, `docs/hardening/STATE-MACHINE.md`.

| از | به | محرک |
|---|---|---|
| draft | scheduled | schedule |
| draft/scheduled | publishing | job/command |
| publishing | published/failed/unknown | نتیجه درایور |
| published | publishing | update |

**P-03:** هر Publication مستقل؛ Batch فقط aggregate نمایشی.

## انتشار گروهی

`PublicationBatchService` → N×Publication → jobs جدا.

To-Be مشروط (FR-015): سیاست retry مقصد شکست بدون duplicate موفق — طراحی در recovery layer، نه تراکنش سراسری بین سرویس‌ها.

## idempotency

`IdempotencyKeyGenerator`, `attempt_token`, intent میزبان — `ReliableIdempotencyTest`.

## زمان‌بندی

`DispatchDuePublicationsCommand` — native scheduling در API کانال نیست (زمان‌بندی پکیج).

## نتیجه مبهم

`UnknownOutcomeTest` — state `unknown`؛ نیاز reconcile (WA batch نمونه).

## هم‌زمانی

`ConnectionTokenRefreshLock`, `StalePublicationTransitionException` — optimistic control on transition.
# تاب‌آوری batch (WP-08-06)

## معیار

یک مقصد ناموفق نباید مانع تکمیل یا موفقیت سایر اهداف شود؛ وضعیت batch باید `partially_completed` منعکس کند.

## شاهد

[PublicationBatchPartialFailureTest](../../tests/Feature/PublicationBatchPartialFailureTest.php):

- دو اتصال؛ یکی با درایور نامعتبر / خطا
- batch به `PublicationBatchStatus::PartiallyCompleted` می‌رسد
- انتشار موفق برای هدف سالم باقی می‌ماند

## بازیابی

`PublicationRecoveryService` برای انتشارات گیرکرده — جدا از batch؛ ر. phase-05 docs.
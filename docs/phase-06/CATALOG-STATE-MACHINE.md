# ماشین حالت همگام‌سازی کاتالوگ

| وضعیت | معنی | ورودی معمول |
|---|---|---|
| `idle` | رکورد ایجاد شده، بدون تغییر معلق | `firstOrCreate` |
| `pending` | snapshot جدید ثبت شده | `recordProductChange` |
| `dispatching` | جاب در حال اجرا | `DispatchPendingCatalogSyncsJob` |
| `synced` | provider یا reconcile خارجی تأیید کرد | `CatalogSyncExecutor` / `CatalogSyncReconciliationService` |
| `failed` | خطای قطعی یا snapshot نامعتبر | validation / provider |
| `unknown` | پذیرش batch واتس‌اپ بدون تأیید نهایی | `WhatsAppDriver::update` |
| `disabled` | sync سراسری یا اتصال خاموش | policy / job |

انتقال‌های نامعتبر (مثلاً `synced` بدون `applied_revision`) در بازیابی legacy طبق [RECOVERY-RUNBOOK.md](RECOVERY-RUNBOOK.md) بررسی می‌شوند.
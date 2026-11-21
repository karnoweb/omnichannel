# WHATSAPP-BATCH-RECONCILIATION

| وضعیت | معنی | recovery |
|--------|------|----------|
| handle دریافت شد | پذیرش اولیه | `PollWhatsAppCatalogBatchJob` |
| pending | نتیجه نامعلوم | reconcile مهلت‌دار |
| partial failure | آیتم‌های ناموفق | `PublicationRecoveryService` |

تست: `WhatsAppCatalogBatchOutcomeTest`, `UnknownOutcomeTest`.
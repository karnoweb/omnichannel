# مبنای فاز ۸

| شکاف | قبل از ۸ | اقدام |
|------|-----------|--------|
| CI فقط SQLite | WP-08-01 باز | job `phpunit-mysql` |
| VERIFICATION MySQL | نامشخص | به‌روز پس از CI |
| PG دوم | — | DEFERRED |
| live staging | — | DEFERRED |
| batch partial | تست موجود | مستند BATCH-RESILIENCE |
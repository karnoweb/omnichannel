# ENVIRONMENT-TEST-MATRIX

| محیط | وضعیت فاز ۵ | شواهد |
|--------|--------------|--------|
| PHP 8.4 + SQLite | اجرا شد | phpunit OK |
| Redis tests | N/A | `--exclude-group redis` |
| MySQL CI | N/A | WP-08-01 فاز بعد |
| Live API | ممنوع | HTTP fake |
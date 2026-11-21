# TEST-REPORT — گام ۵.۱

## فرمان‌ها

```text
vendor\bin\phpunit tests\Feature\UpgradeFrom130Test.php --exclude-group redis
vendor\bin\phpunit --exclude-group redis
```

| فرمان | خروج | نتیجه |
|--------|------|--------|
| UpgradeFrom130Test | 0 | OK (3 tests, 8 assertions) پس از گسترش؛ سپس 3 tests در نسخه نهایی |
| کل مجموعه | 0 | OK (104 tests, 12761 assertions) |

## پوشش WP-05-01

- `test_thirteen_three_migrations_reapply_after_rollback`
- `test_publication_sources_migration_is_idempotent_on_upgrade`
- `test_history_retention_migrations_apply_on_upgrade`

## اجرا نشده

| مورد | دلیل |
|------|------|
| `@group redis` | سیاست CI فعلی — خارج از دامنه ۵.۱ |
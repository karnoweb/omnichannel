# اعتبارسنجی مهاجرت (WP-08-02)

## خودکار (CI)

job [`.gitlab-ci.yml`](../../.gitlab-ci.yml) — `phpunit-mysql`:

- سرویس MySQL 8
- متغیرهای `OMNICHANNEL_TEST_MYSQL_*` + `TestCase::getEnvironmentSetUp`
- `RefreshDatabase` در تست‌ها = migrate squashed روی MySQL
- `UpgradeFrom130Test` در همان suite

## دستی میزبان / dev

1. MySQL 8 با DB خالی
2. export متغیرها (مشابه CI):

```bash
export OMNICHANNEL_TEST_MYSQL_HOST=127.0.0.1
export OMNICHANNEL_TEST_MYSQL_PORT=3306
export OMNICHANNEL_TEST_MYSQL_DATABASE=omnichannel_test
export OMNICHANNEL_TEST_MYSQL_USER=root
export OMNICHANNEL_TEST_MYSQL_PASSWORD=...
```

3. `vendor/bin/phpunit --exclude-group redis`
4. برای مسیر ارتقا: اجرای `UpgradeFrom130Test` و بررسی جداول پس از `2026_11_21_*`

## PostgreSQL

**DEFERRED** — [DECISIONS-AND-BLOCKERS.md](DECISIONS-AND-BLOCKERS.md). تأیید MySQL برای دروازه داده فاز ۸ کافی است.

## rollback تولید

پیش از rollback در تولید: [UPGRADING.md](../../UPGRADING.md) و بررسی `down()` — خارج از دامنه این گزارش.
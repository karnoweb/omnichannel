# شواهد RELEASE-CHECKLIST (WP-09-01)

مرجع: [RELEASE-CHECKLIST.md](../releases/RELEASE-CHECKLIST.md). CI: [`.github/workflows/ci.yml`](../../.github/workflows/ci.yml).

| # | مورد | وضعیت | شاهد |
|---|------|--------|------|
| 1 | طبقه‌بندی semver | PASS | 13.4.0 MINOR |
| 2 | CHANGELOG | PASS | [CHANGELOG.md](../../CHANGELOG.md) `[13.4.0]` |
| 3 | UPGRADING | PASS | مسیر 13.3.1→13.4.0 |
| 4 | docs/providers | PASS | فاز ۶ basalam و غیره |
| 5 | docs/releases/13.4.0.md | PASS | |
| 6 | ماتریس قابلیت | PASS | بدون ادعای جدید بدون تست |
| 7 | PHPUnit | PASS | 158 tests محلی؛ GHA `phpunit` |
| 8 | ارتقا | PASS | `UpgradeFrom130Test` |
| 9 | MySQL | PASS | GHA `phpunit-mysql` |
| 10 | Redis | PASS | GHA `redis` |
| 11 | میزبان | N/A | خارج پکیج |
| 12 | API زنده | DEFERRED | فاز ۸ |
| 13 | version/tag | PASS | `v13.4.0` |
| 14 | rollback | PASS | UPGRADING + MIGRATION-REVIEW ارجاع |
| 15 | Known limitations | PASS | 13.4.0.md |
| 16 | تاریخ انتشار | PASS | پس از tag |
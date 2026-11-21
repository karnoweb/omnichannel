# WORK-PACKAGE — WP-05-01

| فیلد | مقدار |
|------|--------|
| شناسه | WP-05-01 |
| عنوان | گسترش تست ارتقا ۱۳.۲→۱۳.۳ |
| منبع | FR-038, P43-GAP-007, OMC-AUD-007 |
| اولویت | P0 |

## محدوده

- `tests/Feature/UpgradeFrom130Test.php`
- پوشش migrationهای `2026_11_21_000001` تا `000005`

## غیرهدف

- تغییر `src/` مگر باگ اثبات‌شده در همان گام (انجام نشد)
- درایورها، Graph، catalog sync فعال‌سازی

## معیار پذیرش

- مسیر rollback/reapply و idempotent `000001` در PHPUnit سبز
- `phpunit --exclude-group redis` بدون شکست ناشی از این تغییر
# SHARED-DRIVER-BASELINE

خط مبنای مشترک درایورها برای گام‌های ۵.۲–۵.۸ (مبتنی بر کد `13.3.0`).

## قرارداد هسته

| مؤلفه | مسیر | مسئولیت |
|--------|------|---------|
| `ChannelDriver` | `src/Contracts/ChannelDriver.php` | validate، test، publish، update، delete |
| `ChannelCapabilities` | `src/ValueObjects/ChannelCapabilities.php` | اعلام قابلیت + دلیل عدم پشتیبانی |
| `ChannelManager` | `src/Services/ChannelManager.php` | resolve درایور از config |
| `PublishingService` | `src/Services/PublishingService.php` | چرخه انتشار، deactivate اختیاری |
| `PublicationRecoveryService` | `src/Services/PublicationRecoveryService.php` | reconcile برای `ReconcilableChannelDriver` |

## لایه HTTP مشترک

| مؤلفه | مسئولیت |
|--------|---------|
| `BaseApiClient` | درخواست، شمارش، خطا، Retry-After |
| `ApiRequestRateLimiter` | سقف به‌ازای حساب/scope |
| `ConnectionTokenRefreshLock` | هم‌زمانی تمدید توکن |
| `ApiResponse` | نرمال‌سازی پاسخ |

## BotApi (تلگرام / بله)

- `BotApiDriver` + `BotApiClient`: متدهای Telegram-like؛ **بله** `supportsParseMode(): false`.
- خطا: `BotApiErrorMapper`.

## نتایج استاندارد

`PublishResult`, `DeleteResult`, `ValidationResult`, `ConnectionTestResult` — بدون موفقیت صوری وقتی قابلیت اعلام‌نشده است.

## تست‌های مشترک مرجع

`DriverContractTest`, `UnknownOutcomeTest`, `SharedAccountRateLimitTest`, `BranchIsolationTest`, `ReliableIdempotencyTest`, `QueueRetryPolicyTest` — در گام ۵.۸ بازبینی می‌شوند.

## محدودیت‌های ثابت

- قیمت/موجودی از میزبان؛ `catalog_sync.enabled` پیش‌فرض `false`.
- بدون وابستگی `karno-base`.
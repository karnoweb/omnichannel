# INSTAGRAM-AUTH-AND-RECOVERY

## توکن

- ذخیره در `ChannelConnection::credentials` (رمزگذاری میزبان).
- `ConnectionTokenRefreshLock` برای تمدید هم‌زمان.

## خطاهای نمونه

| کد/کلاس | بازیابی |
|---------|---------|
| توکن منقضی | تمدید + retry با قفل |
| container `ERROR` | PublicationRecoveryService در صورت پشتیبانی reconcile |
| rate limit | Retry-After در BaseApiClient |

**DEC-006:** bump نسخه Graph بدون مصوبه انجام نشد — همچنان `v26.0` در config.
# درایور Demo (`demo`) — مستند داخلی

**تاریخ:** 2026-11-21 | **منبع:** فقط سورس داخلی — بدون API خارجی

## هدف

شبیه‌سازی کانال اجتماعی برای تست Omnichannel بدون فراخوانی HTTP خارجی.

## پیکربندی

| کلید | پیش‌فرض | نقش |
|---|---|---|
| `base_url` | `https://demo.local` | URL ساختگی نتیجه |
| `transient_failure_rate` | 0 | احتمال خطای گذرا |
| `latency_ms` | 0 | تأخیر مصنوعی |

## قابلیت‌های شبیه‌سازی‌شده (`DemoDriver.php`)

Post، Carousel، Reel، Story، Update، Delete، Scheduling (محلی)، Hashtags، AltText — **بدون** Product/Catalog/Message/Album/Pin.

## رفتار اتصال

- `testConnection`: نیاز `access_token` در credentials؛ `force_test_failure` برای شکست آزمایشی.
- Idempotency: Cache 24h + `persistentArtifact`.

## تفاوت با سرویس واقعی

هیچ quota، OAuth، یا webhook خارجی وجود ندارد. خطاها از `metadata.demo.force` یا نرخ تصادفی.

## سناریوهای تست داخلی

| شناسه | توضیح |
|---|---|
| DEMO-VAL-001 | publish موفق با external_id ساختگی |
| DEMO-VAL-002 | replay idempotency |
| DEMO-VAL-003 | transient_failure_rate > 0 |

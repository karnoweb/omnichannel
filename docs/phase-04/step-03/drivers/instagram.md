# Instagram — ماتریس تفصیلی

منبع رسمی: [providers/instagram.md](../../providers/instagram.md) | سورس: `src/Channels/Instagram/`

## جدول وضعیت

| P43-ROW | شناسه | قابلیت | رسمی | اعلام | پیاده‌سازی | دسترسی | تطابق | دامنه |
|---|---|---|---|---|---|---|---|---|
| P43-ROW-001 | OMC-CAP-002 / IG-CAP-001 | انتشار پست تک‌رسانه | مشروط Professional | اعلام | کامل | عمومی `PublishingService` | اختلاف نسخه v26 | فعلی |
| P43-ROW-002 | OMC-CAP-005 / IG-CAP-002 | کاروسل | مشروط 50/24h | اعلام | کامل | همان | اختلاف نرخ داخلی | فعلی |
| P43-ROW-012 | OMC-CAP-006 | Reel / Story | مشروط | اعلام | کامل | همان | مطابق محدوده | فعلی |
| P43-ROW-013 | OMC-CAP-009 | ویرایش کپشن | محدود API | اعلام | کامل | همان | نامشخص جزئیات | فعلی |
| P43-ROW-014 | OMC-CAP-010 | حذف | مشروط FB Login | **ند** | اعلام نشده | مسیر publish | عدم پشتیبانی Login IG | فعلی |
| P43-ROW-015 | OMC-CAP-007 | محصول | — | ند | ند | — | نامرتبط | خارج |

## جدول شواهد

| P43-ROW | عملیات | کد | تست | شکاف |
|---|---|---|---|---|
| P43-ROW-001 | IG-OP-001,002 | `InstagramApiClient`, `InstagramDriver` | `DriverContractTest`, Instagram feature | P42-DIFF-002 |
| P43-ROW-002 | IG-OP-001 carousel | همان | همان | P42-DIFF-001 → P43-GAP-002 |
| P43-ROW-014 | — | `InstagramDriver::capabilities()` خط 57 | Contract unsupported delete | — |

## جمع‌بندی

- **قوت:** پوشش Post/Carousel/Reel/Story و رسانه؛ شواهد تست محلی قوی.
- **ناقص:** حذف برای مدل Login فعلی.
- **محدودیت رسمی:** سقف انتشار، نوع حساب، مجوزها.
- **موانع:** P42-Q-003، P42-Q-006.
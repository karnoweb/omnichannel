# سیاست صف و تلاش مجدد

## رفتار فعلی پس از STEP 08

- درخواست HTTP تغییردهنده به‌طور پیش‌فرض retry نمی‌شود؛ retry انتقال فقط برای درخواست‌هایی فعال است که caller آن‌ها را safe علامت بزند.
- job انتشار در زمان ساخت یک deadline ثابت ذخیره می‌کند؛ retryهای همان job آن را تمدید نمی‌کنند. jobهای قدیمی که پیش از این نسخه serialize شده‌اند، deadline زمان اجرا می‌سازند تا با property تازهٔ غایب crash نکنند.
- backoff پیش‌فرض 30، 120، 300 و 900 ثانیه است و jitter مثبت تا سقف OMNICHANNEL_JOB_BACKOFF_JITTER (پیش‌فرض ۱۰ ثانیه) اضافه می‌شود. فهرست خالی/نامعتبر به پیش‌فرض امن برمی‌گردد.
- زمان‌بند قبل از dispatch برای publication و زمان scheduled یک reservation اتمیک cache می‌گذارد؛ job زمان همان برنامه را snapshot می‌کند و پیش از claim دوباره تطبیق می‌دهد. رزرو مشترک بین schedulerها نیازمند cache مشترک است و با TTL پیکربندی‌شده آزاد می‌شود.
- تمدید token در Basalam/Instagram زیر cache lock اتصال اجرا می‌شود؛ connection پس از قفل تازه می‌شود و اگر worker قبلی token/expiry را عوض کرده باشد، درخواست دوم ارسال نمی‌شود. timeout قفل فعلاً شکست امن و بدون ارسال شبکه‌ای می‌دهد.
- Rate limit از مقدار retry_after بدنهٔ Bot API یا هدر Retry-After عددی/تاریخی استفاده می‌کند؛ اگر موجود نباشد fallback برابر OMNICHANNEL_RATE_LIMIT_FALLBACK (پیش‌فرض ۳۰۰ ثانیه) است. jitter فقط به زمان انتظار سرور اضافه می‌شود و آن را کوتاه نمی‌کند.
- خطای UnknownOutcome، Authentication، Permission، Validation و Unsupported خودکار retry نمی‌شوند. نتیجهٔ مبهم برای ارسال مجدد به بررسی و تأیید صریح نیاز دارد.

## محدودیت‌های باز

- استخراج Retry-After و زمان‌بندی مجدد تا اینجا برای metadata پاسخ Bot API متصل شده؛ driverهای دیگر ممکن است فقط FailureClass بدهند و header را به job نرسانند.
- ThrottleChannelRequests فعلی یک hit به‌ازای job با کلید driver ثبت می‌کند. این quota درخواست واقعی، upload، polling یا فراخوانی مستقیم سرویس نیست و بین workerها فقط وقتی cache مشترک باشد مؤثر است.
- deadline ثابت برای job جدید پایدار است، اما deadline مطلق در رکورد Publication ذخیره نمی‌شود؛ پس از پایان/ساخت job تازه، پنجرهٔ مجاز از نو آغاز می‌شود.
- e2e queue با backend چندپردازشی، زمان‌بندی بر مبنای header تاریخ‌دار، retryAfter برای تمام driverها و تخلیهٔ migration صف باید در STEP 09 آزموده شوند.

## منابع تنظیمات

- omnichannel.queue.tries
- omnichannel.queue.backoff
- omnichannel.queue.backoff_jitter_seconds
- omnichannel.queue.rate_limited_fallback_seconds
- omnichannel.queue.retry_until_minutes
- omnichannel.queue.scheduled_dispatch_reservation_seconds
- omnichannel.queue.token_refresh_lock_seconds
- omnichannel.queue.token_refresh_lock_wait_seconds
- omnichannel.channels.{driver}.rate_limit

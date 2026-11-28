# شواهد آزمون درایورها

DriverContractTest ثبت دقیق هر ۹ درایور، رد اتصال بدون اعتبارنامه، رد نوع محصول برای درایورهای فاقد آن و رد عملیات ویرایش و حذف اعلام‌نشده را بدون درخواست بیرونی بررسی می‌کند.

| درایور | شاهد اختصاصی |
|---|---|
| Instagram | InstagramDriverOperationsTest، اتصال، انتشار چهار نوع، ویرایش مجاز، بررسی محدود وضعیت و جلوگیری از ارسال دوباره؛ SharedAccountRateLimitTest برای سهمیه |
| Telegram | MessengerDriverOperationsTest، BaleBotApiParseModeTest |
| Bale | MessengerDriverOperationsTest، BaleBotApiParseModeTest |
| WhatsApp | WhatsAppCatalogBatchOutcomeTest |
| Basalam | BasalamDriverOperationsTest، احراز غرفه، بارگذاری عکس، ایجاد، ویرایش، غیرفعال‌سازی و رد حذف |
| Rubika | MessengerDriverOperationsTest، اتصال، انتشار متن، ویرایش و حذف |
| Gap | MessengerDriverOperationsTest، اتصال، انتشار متن، ویرایش و حذف |
| Eitaa | MessengerDriverOperationsTest، انتشار متن و بررسی محلی پیکربندی |
| Demo | UnknownOutcomeTest، چرخه اتصال تا حذف بدون درخواست بیرونی |

برای هر ۹ درایور یک مسیر معتبر برای هر عملیات اعلام‌شده آزموده شد. آزمون‌ها بر پاسخ‌های مصنوعی و قابلیت‌های پیکربندی‌شده تکیه دارند. ماتریس قابلیت‌های اعلامی در [مرحله ۵.۸](../step-08/FINAL-DRIVER-CAPABILITY-MATRIX.md) آمده است.

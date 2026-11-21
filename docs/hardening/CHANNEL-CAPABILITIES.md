# رفتار کانال‌ها و شواهد پیاده‌سازی

این سند snapshot رفتار اعلام‌شده در نسخهٔ فعلی درایورهاست؛ منبع حقیقت برای قابلیت‌های اجرایی، متد `capabilities()` و متدهای driver است. این جدول نتیجهٔ آزمون زنده روی حساب خارجی نیست. محدودیت‌های API ممکن است با نوع ورود، مجوز، نسخه یا قرارداد درگاه واسط فرق کنند؛ موردهای وابسته به این عوامل باید پیش از تغییر قابلیت با مستند رسمی و آزمون integration دوباره بررسی شوند.

## حذف و غیرفعال‌سازی

| کانال | Delete | Deactivate | نکتهٔ پیاده‌سازی |
| --- | --- | --- | --- |
| Demo | بله | خیر | حذف داخلی شبیه‌سازی‌شده؛ رسانهٔ واقعی حذف نمی‌شود. |
| Instagram | خیر | خیر | درایور از Instagram Login استفاده می‌کند و حذف را پشتیبانی‌شده اعلام نمی‌کند. |
| WhatsApp Catalog | بله | بله | محصول کاتالوگ است، نه پیام یا پست؛ مسیر حذف و غیرفعال‌سازی جدا هستند. |
| Basalam | خیر | بله | API عمومی درایور endpoint حذف ندارد؛ غیرفعال‌سازی وضعیت محصول را به پیش‌نویس می‌برد. |
| Gap | بله | خیر | پیام تکی؛ گروه رسانه‌ای ندارد. |
| Rubika | بله | خیر | پیام تکی؛ گروه رسانه‌ای ندارد. |
| Telegram | بله | خیر | حذف پیام با شناسهٔ پیام انجام می‌شود؛ محدودیت زمانی عملیاتی باید با نسخهٔ مستند Bot API تطبیق داده شود. |
| Bale | بله | خیر | حذف پیام؛ درایور محدودیت ۴۸ ساعته را اعلام می‌کند. |
| Eitaa / Eitaayar | خیر | خیر | gateway درایور نه ویرایش، نه حذف و نه گروه رسانه‌ای را ارائه می‌دهد. |

«بله» یعنی درایور این قابلیت را در قرارداد داخلی اعلام و برایش مسیر کد دارد؛ موفقیت هر درخواست همچنان به دسترسی اتصال و سیاست API خارجی بستگی دارد. غیرفعال‌سازی به‌تنهایی وضعیت انتشار را Deleted نمی‌کند.

## قالب‌ها و ویرایش

| کانال | قالب یا ویژگی مرتبط | فیلدهای Update اعلام‌شده |
| --- | --- | --- |
| Demo | پست، carousel، reel، story | caption، hashtags، title، media.*، extra.*؛ پاک‌کردن همه |
| Instagram | پست، carousel، reel، story؛ Carousel قالب گروهی آن است | caption، hashtags؛ پاک‌کردن هر دو مجاز |
| WhatsApp Catalog | محصول کاتالوگ؛ تصویر دارد و ویدیو پشتیبانی نمی‌شود | title، caption، media.image، extra.price/stock/currency/category؛ پاک‌کردن اعلام نشده |
| Basalam | محصول و گالری عکس | title، caption، hashtags/keywords، extra.price/stock؛ پاک‌کردن اعلام نشده |
| Gap | پیام متنی/رسانه‌ای؛ آلبوم ندارد | caption، hashtags |
| Rubika | پیام متنی/رسانه‌ای؛ آلبوم ندارد | caption، hashtags |
| Telegram | پیام متنی/رسانه‌ای و آلبوم | caption، hashtags |
| Bale | پیام متنی/رسانه‌ای و آلبوم | caption، hashtags |
| Eitaa / Eitaayar | پیام متنی/رسانه‌ای تک‌فایلی | Update پشتیبانی نمی‌شود |

فهرست فیلدها محافظه‌کارانه است: سرویس تغییر فیلد خارج از فهرست را پیش از درخواست رد می‌کند. وجود `Update` به معنی امکان تغییر تمام محتوای canonical نیست.

## محدودیت نرخ پیکربندی‌شده

این اعداد سیاست پیش‌فرض داخلی صف در `config/omnichannel.php` هستند و نباید سهمیهٔ رسمی API تلقی شوند. middleware فعلی برای هر job یک hit با کلید درایور ثبت می‌کند؛ فراخوانی مستقیم، عملیات چندمرحله‌ای و چند endpoint یک ارسال به‌ازای هر درخواست واقعی را نمی‌شمارند. بنابراین این جدول بیانگر quota (سهمیه) تأییدشدهٔ سرویس خارجی نیست.

| کانال | سقف داخلی فعلی |
| --- | --- |
| Demo | 60 در 60 ثانیه |
| Instagram | 25 در 3600 ثانیه |
| Basalam | 60 در 60 ثانیه |
| Telegram، Bale، Rubika، Gap، Eitaa / Eitaayar | هرکدام 20 در 60 ثانیه |
| WhatsApp Catalog | 60 در 3600 ثانیه |

Meta برای Marketplace Partner Item API در صفحهٔ رسمی سهمیهٔ 30 درخواست در دقیقه و حداکثر 300 محصول در هر batch را توصیه/ذکر می‌کند؛ این قرارداد را نمی‌توان بدون تأیید نوع حساب به همهٔ WhatsApp Catalogها تعمیم داد. مقدار داخلی WhatsApp فعلی با آن راهنما هم‌راستا نیست و باید پس از تعیین نوع حساب و endpoint معتبر بازبینی شود.

## آلبوم‌های Bot API

- Telegram و Bale از کلاس مشترک `BotApiDriver` و `sendMediaGroup` استفاده می‌کنند؛ پاسخ لیستی می‌تواند برای هر پیام یک `message_id` داشته باشد.
- شناسهٔ اول همچنان در `publications.external_id` می‌ماند تا مصرف‌کنندگان نسخهٔ فعلی سازگار بمانند؛ تمام شناسه‌ها در artifact پایدار `publish/external_ids` ذخیره می‌شوند.
- replay از artifact پایدار شناسه‌ها استفاده می‌کند؛ Cache فقط تسریع‌کننده است.
- حذف شناسه‌ها را ترتیبی پردازش می‌کند. بعد از هر حذف تأییدشده، فهرست باقی‌مانده ثبت می‌شود. خطا، فهرست حذف‌شده/باقی‌مانده را در نتیجه و log نگه می‌دارد و retry بعدی از باقی‌مانده شروع می‌کند.
- سابقهٔ قدیمی که فقط `external_id` دارد، فقط همان عضو شناخته‌شده را حذف می‌کند؛ اعضای گمشده حدس زده نمی‌شوند. نتیجهٔ قدیمی باید با این محدودیت تفسیر شود.

## WhatsApp Catalog: batch ناهمگام

مشاهدهٔ مستندات رسمی Meta در ۲۰۲۶-۱۱-۲۰: درخواست `items_batch` می‌تواند `handle` برگرداند؛ وضعیت batch با `check_batch_request_status` پیگیری می‌شود و وضعیت Finished به معنی تمام‌شدن پردازش است، هرچند درخواست‌های دارای خطا ذخیره نشده‌اند. محدودیت نرخ برای batch به شناسهٔ catalog وابسته است. این شواهد جای آزمون با مجوزها و نوع حساب واقعی این اتصال را نمی‌گیرد.

- [Catalog webhook reference](https://developers.facebook.com/docs/graph-api/webhooks/reference/catalog/) — فیلد `items_batch` شامل `handle` است و برای بررسی وضعیت قابل استفاده است.
- [Marketplace Partner Item API](https://developers.facebook.com/docs/marketplace/partnerships/itemAPI/) — نمونهٔ ایجاد batch و بررسی `check_batch_request_status` با handle؛ قرارداد وابسته به Marketplace Partner API است.
- [Graph API rate limiting](https://developers.facebook.com/docs/graph-api/overview/rate-limiting/) — batch کاتالوگ مشمول محدودیت درخواست مبتنی بر catalog ID است.

قرارداد امن داخلی: پاسخ HTTP موفق به‌تنهایی پایان عملیات نیست. `WhatsAppDriver` اکنون handle، زمان پذیرش و پاسخ پاک‌سازی‌شده را در artifact پایدار ثبت می‌کند، نتیجه را `UnknownOutcome` می‌دهد و هر mutation دیگری برای همان انتشار را تا رفع ابهام متوقف می‌کند. شناسهٔ کاتالوگ در `external_id` نوشته نمی‌شود. `WhatsAppCatalogClient::checkBatchRequestStatus` مسیر GET امن برای خواندن وضعیت handle را فراهم می‌کند؛ اتصال آن به poller صف و نهایی‌سازی مدل انتشار هنوز انجام نشده و عملیات تا آن زمان مبهم می‌ماند. پاسخ نهایی شامل تعداد و شرح خطاهاست؛ پایان batch لزوماً به معنی موفقیت تک‌تک درخواست‌ها نیست.

برای بررسی دستی یک انتشار و ذخیرهٔ خلاصهٔ وضعیت/خطاها: `php artisan omnichannel:inspect-whatsapp-batch {publication_id}`؛ برای محدودکردن به یک عملیات از `--operation=create|update|delete|deactivate` استفاده کنید. فرمان فقط GET می‌فرستد، خروجی محدود و بدون token چاپ می‌کند و وضعیت Publication را تغییر نمی‌دهد. پاسخ API در metadata با credentialهای همان اتصال پاک‌سازی می‌شود. این مسیر برای بررسی عملیاتی است، نه poller خودکار.

## منابع داخل مخزن

- قابلیت‌های هر کانال در `src/Channels/{Demo,Instagram,WhatsApp,Basalam,Gap,Rubika,Telegram,Bale,Eitaa}/*Driver.php` تعریف شده‌اند.
- Bot API مشترک و استخراج/ذخیرهٔ شناسه‌های آلبوم در `src/Channels/Messaging/BotApiDriver.php` است.
- سیاست وضعیت حذف و نگهداری سابقه در `src/Services/PublishingService.php` و `src/Models/Publication.php` است.
- این snapshot باید در همان تغییر pull request با هر تغییر capability به‌روز شود؛ تأیید بیرونی API در تست‌های integration مرحلهٔ ۰۹ ثبت می‌شود.
- سیاست صف، headerهای retry و محدودیت محدودکنندهٔ فعلی در `docs/hardening/RETRY-POLICY.md` نگهداری می‌شود.


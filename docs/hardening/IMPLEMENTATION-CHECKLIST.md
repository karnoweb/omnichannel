# چک‌لیست مقاوم‌سازی هسته

تاریخ: 2026-11-20. گام‌های مهندسی STEP 00 تا STEP 08 اجرا شده‌اند؛ موارد ناقص و نیازمند اعتبارسنجی عملیاتی در گزارش پایهٔ [Phase 02](../phase-02/BASELINE-AUDIT.md) تفکیک شده‌اند. [AUDIT.md](AUDIT.md) گزارش تاریخی پیش از تغییرات نسخهٔ 13.0.0 است؛ وضعیت فعلی هر مرحله در همین چک‌لیست آمده است. سیاست‌ها در [SECURITY.md](SECURITY.md)، انتقال‌ها در [STATE-MACHINE.md](STATE-MACHINE.md) و تصمیم‌ها در [DECISIONS.md](DECISIONS.md) ثبت شده‌اند.

## STEP 00 — ممیزی اولیه

- [x] ساختار، قراردادها، تنظیمات، مدل‌ها، ساختار پایگاه داده و آزمون‌ها بررسی شدند.
- [x] مسیرهای ایجاد، انتشار، ویرایش، حذف، زمان‌بندی، لغو و تلاش مجدد ثبت شدند.
- [x] مرزهای ورود، ذخیره و نمایش اطلاعات حساس مشخص شدند.
- [x] هر ۹ درایور از نظر مسیرهای هسته مرور شدند.
- [x] مصرف‌کنندگان میزبان در دسترس و خواننده اختصاصی رسانه شناسایی شدند.
- [x] مشکلات ادعاشده با کد تطبیق و محافظت‌های ازقبل‌موجود جدا شدند.
- [x] وضعیت پایه صادقانه ثبت شد: نحو ۱۰۸ فایل صحیح؛ آزمون رفتاری قابل اجرا نبود.
- [x] شدت، شرایط، شاهد، راه‌حل، فایل‌های هدف، آزمون و معیار پذیرش هر یافته ثبت شد.
- [x] برنامه مستقل و ترتیبی مراحل بعد آماده شد.
- [x] هیچ تغییر اجرایی یا پایگاه داده انجام نشد.

## STEP 01 — محافظت از اسرار

- [x] پاک‌سازی بازگشتی کلیدها، URLها، headerهای متنی، Bearer/Basic و مقادیر محرمانهٔ اتصال.
- [x] محدودسازی اندازهٔ جزئیات گزارش پس از پاک‌سازی.
- [x] پاک‌سازی خطاها، پاسخ‌ها و نتیجه‌ها در مسیر انتشار/ویرایش/حذف/تست اتصال/Job.
- [x] محافظت JSON اتصال، انتشار و گزارش از نمایش دادهٔ حساس.
- [x] فرمان تاریخچه با dry-run پیش‌فرض و `--apply` صریح.
- [x] تست‌های ساختگی برای redactor، API، logger، JSON مدل و dry-run/apply فرمان.
- [x] اجرای مجموعهٔ PHPUnit مرحلهٔ ۰۱: 7 تست، 5153 assertion، موفق؛ Pint برای فایل‌های تغییرکرده پاس شد.
- [x] handler درخواست Livewire میزبان در debug روشن با token ساختگی آزموده و پاک شد.
- [ ] اجرای کامل تست میزبان: یک شکست نامرتبط در `HubDevelopmentRoadmapTest` (انتظار ۸ کارت، دریافت ۷)؛ با اجرای مستقل نیز بازتولید شد.

## STEP 02 — جداسازی شعب و زمینهٔ صف

- [x] زمینهٔ بدون branch ID برای query و عملیات انتشار fail-closed است.
- [x] دسترسی اتصال و انتشار با branch ID جاری تطبیق داده می‌شود؛ اتصال global فقط در system context صریح.
- [x] `forBranch()` یک شعبه را انتخاب می‌کند و انتخاب شعبهٔ دیگر را بدون system context رد می‌کند.
- [x] Job انتشار به شعبهٔ رکورد scope می‌شود و context را پس از اجرا/خطا بازمی‌گرداند.
- [x] فرمان‌های زمان‌بند برای query بین‌شعبه‌ای system context صریح دارند.
- [x] قرارداد `BranchScopedProductRepository` و adapter میزبان، محصول را با branch ID جست‌وجو می‌کنند.
- [x] تست‌های پکیج: 15 تست، 5419 assertion؛ موفق.
- [x] تست adapter انتشار میزبان: 8 تست، 247 assertion؛ موفق.
- [ ] تست کامل میزبان اجرا نشده؛ شکست قبلی نامرتبط `HubDevelopmentRoadmapTest` همچنان ثبت است.

## وضعیت مراحل

- [x] STEP 03 — دریافت امن رسانه و هماهنگی خواننده میزبان.
- [x] STEP 04 — جلوگیری از تکرار ناامن و نتیجه نامشخص.
- [x] STEP 05 — انتقال وضعیت شرطی، مالکیت تلاش و ثبت نتیجه منسجم.
- [x] STEP 06 — ویرایش امن محتوای تأییدشده.
- [x] STEP 07 — حذف، آلبوم، غیرفعال‌سازی و حفظ سابقه؛ پیگیری خودکار batch واتس‌اپ و ارتقای پایگاه داده به STEP 09 منتقل شده.
- [x] STEP 08 — سخت‌سازی retry، زمان‌بند و تمدید token؛ سهمیهٔ واقعی API و تأیید backend cache عملیاتی باز هستند.
- [ ] STEP 09 — آزمون تجمیعی، نصب تازه، ارتقا و گزارش نهایی.

## شرایط خروج هر مرحله

- [ ] آزمون بازتولید پیش از اصلاح ثبت شود.
- [ ] آزمون جدید و آزمون‌های قبلی اجرا و نتایج واقعی ثبت شوند.
- [ ] اثر تغییر روی هر ۹ درایور و قراردادهای میزبان بررسی شود.
- [ ] معیارهای پذیرش، تغییر ناسازگار، خطر باقی‌مانده و محدودیت بازگشت مستند شوند.
- [ ] گزارش مرحله و مستندات مرتبط تکمیل شوند.

## گزارش پایان STEP 03

| فیلد گزارش | نتیجه |
| --- | --- |
| Problems Confirmed | SSRF (درخواست جعل‌شده سمت سرور)، redirect، پاسخ بدون سقف واقعی و خواندن مسیر محلی خارج از ریشه قابل‌سوءاستفاده بودند. |
| Files Created | `tests/Feature/HttpMediaBytesResolverSecurityTest.php`. |
| Files Modified | resolver، config، adapter میزبان، `SECURITY.md` و `DECISIONS.md`. نسخهٔ پکیج میزبان با منبع همگام شد. |
| Changes Implemented | اعتبارسنجی URL و IP عمومی، pin کردن DNS، redirect خاموش، stream به فایل موقت، سقف ۵۰ MiB قابل تنظیم، تشخیص MIME واقعی، cleanup؛ containment و سقف برای مسیر محلی. |
| Database Changes | هیچ‌کدام. |
| Breaking Changes | نشانی غیر HTTP(S)، نوع غیرتصویر/ویدیو و رسانه بزرگ‌تر از سقف پیش‌فرض رد می‌شوند. |
| Tests Added | چهار تست: مقصد خصوصی، redirect، سقف اندازه و MIME جعلی. |
| Test Results | پکیج: 19 تست و 5480 assertion پاس؛ Pint و lint resolver/test پاس؛ adapter میزبان lint و Pint پاس. اجرای تست پکیج در vendor میزبان ممکن نبود چون Testbench نصب نیست؛ hash فایل‌های نصب‌شده با منبع تطبیق شد. |
| Remaining Risks | fake انتقال Guzzle/Sink را کامل شبیه‌سازی نمی‌کند؛ آزمون شبکه محلی برای اندازه بدون Content-Length و تغییر DNS حین درخواست لازم است. Adapter میزبان تست اختصاصی traversal/symlink ندارد. |
| Acceptance Criteria Status | IP داخلی، redirect، پاسخ بزرگ و MIME جعلی رد می‌شوند و فایل موقت در finally پاک می‌شود. انتقال واقعی بدون Content-Length و DNS rebinding به تست integration مرحله ۰۹ موکول است. |

## گزارش پایان STEP 04

| فیلد گزارش | نتیجه |
| --- | --- |
| Problems Confirmed | کلید intent شعبه/زمان/تکرار آگاهانه را جدا نمی‌کرد؛ تمام درخواست‌های HTTP پس از خطای ارتباطی retry می‌شدند؛ نتیجه مبهم به Failed قابل retry تبدیل می‌شد و artifactهای مرحله‌ای فقط در cache بودند. |
| Files Created | `tests/Feature/ReliableIdempotencyTest.php`, `PublicationIntentCreationTest.php`, `UnsafeRequestRetryTest.php`, `UnknownOutcomeTest.php`. |
| Files Modified | generator، `PublishingService`, `BaseApiClient`, `Publication`, `PublicationStatus`, `FailureClass`, logger، queue job، درایورهای دارای replay/upload، ترجمه‌ها و مستندات hardening. فایل‌های پکیج میزبان با منبع همگام شدند. |
| Changes Implemented | کلید نسخه ۲ شامل اتصال/شعبه/محصول/نوع/محتوای canonical/زمان UTC و intent؛ بازیابی کلید قدیمی؛ retry خاموش پیش‌فرض و opt-in صریح؛ وضعیت پایدار `unknown_outcome`، عدم retry خودکار و تأیید دستی ثبت‌شده؛ artifactهای انتشار/آپلود/کانتینر در metadata رکورد. claim شرطی انتشار حفظ شد. |
| Database Changes | migration لازم نشد؛ ستون‌های `status`, `failure_class` و `metadata` از قبل رشته/JSON و سازگار با مقادیر جدید بودند. |
| Breaking Changes | retry فنی از نتیجه مبهم خودکار انجام نمی‌شود. برای retry دستی نتیجه مبهم باید `confirmUnknownOutcome: true` داده شود. `allowDuplicate` برای تکرار فنی پایدار به `intentId` ثابت نیاز دارد. |
| Tests Added | چهار فایل تست؛ بررسی canonicalization، رسانه مرتب، زمان UTC، شعبه، intent پایدار، کلید قدیمی، post غیرایمن، read با retry صریح، UnknownOutcome، retry دستی، cache loss، و claim هم‌زمان. |
| Test Results | مجموعه پکیج: 29 تست و 5875 assertion پاس. Pint روی فایل‌های تغییرکرده پاس شد. |
| Remaining Risks | قابلیت تطبیق وضعیت با API پلتفرم‌ها پیاده‌سازی نشده؛ در نبود پشتیبانی API، نتیجه مبهم نیازمند بررسی انسانی است. آزمون شبکه قطع‌شده پس از دریافت پاسخ خارجی در fake کامل شبیه‌سازی نشده است. artifact metadata باقی‌مانده در رکورد پاک‌سازی دوره‌ای ندارد. تضمین exactly-once ادعا نمی‌شود. |
| Acceptance Criteria Status | claim تنها یک worker را می‌پذیرد؛ HTTP send با خطای ارتباطی تکرار نمی‌شود؛ موفقیت DB از cache مستقل است؛ retry نتیجه مبهم نیازمند تأیید و log است؛ کلید legacy از ایجاد رکورد مجدد محافظت می‌کند. |

## گزارش پایان STEP 05

| فیلد گزارش | نتیجه |
| --- | --- |
| Problems Confirmed | شکست پیش از ارسال status را Failed نمی‌کرد؛ claim شمارنده را از نمونهٔ مدل می‌خواند؛ انتقال وضعیت شرط پایگاه داده نداشت؛ نتیجه و log در تراکنش واحد ثبت نمی‌شدند؛ job شکست‌خورده می‌توانست تلاش متوقف را به خطای قابل تکرار تبدیل کند. |
| Files Created | `StalePublicationTransitionException.php`, migration افزودن `attempt_token`, `PublicationStateMachineTest.php`, `STATE-MACHINE.md`, `UPGRADE-GUIDE.md`. |
| Files Modified | `PublicationStatus`, `Publication`, `PublishingService`, `PublishPublicationJob`, ترجمه‌ها، audit و تصمیم‌ها. |
| Changes Implemented | جدول انتقال رسمی؛ Draft/Scheduled نامعتبر → Failed بدون افزایش تلاش؛ claim و شمارنده شرطی/اتمیک؛ token مالک تلاش و جلوگیری از ثبت نتیجهٔ کهنه؛ نتیجه و log در تراکنش کوتاه؛ رویداد پس از commit؛ وضعیت Deleted و علامت‌گذاری موفقیت حذف؛ query رکوردهای متوقف بدون retry خودکار. |
| Database Changes | migration افزایشی برای UUID nullable و index روی `attempt_token`; backfill لازم نیست. |
| Breaking Changes | خطای پیش‌ارسال اکنون status را به Failed تغییر می‌دهد؛ consumerهایی که روی status دقیق حساب می‌کنند باید این رفتار را در نظر بگیرند. برای ارتقا workerها را هنگام migration تخلیه و بعد با کد جدید راه‌اندازی کنید. |
| Tests Added | هفت سناریو در `PublicationStateMachineTest`: جدول انتقال، claim/شمارنده، اعتبارسنجی زمان‌بندی‌شده، نتیجهٔ کهنه، رکورد متوقف، event پس از commit و خطای listener. |
| Test Results | مجموعهٔ پکیج: 36 تست و 6255 assertion پاس؛ آزمون جدید مرحله: 7 تست و 43 assertion پاس. |
| Remaining Risks | رقابت واقعی چندپردازه روی پایگاه دادهٔ تولیدی و شکست تزریقی پایگاه داده در مرز پاسخ خارجی به STEP 09 موکول است. برای رکورد Publishing متوقف، ابزار مشاهدهٔ خودکار سراسری ارائه نشده؛ query مدل آن را قابل یافتن می‌کند و retry خودکار نمی‌شود. |
| Acceptance Criteria Status | claim تکراری رد می‌شود؛ Scheduled نامعتبر از زمان‌بند خارج می‌شود؛ لغو و انتقال قدیمی اتمیک‌اند؛ فقط مالک تلاش نتیجه را ثبت می‌کند؛ event پس از commit اجرا می‌شود؛ زمان سپری‌شده به‌تنهایی رکورد متوقف را retry نمی‌کند. |

## گزارش پایان STEP 06

| فیلد گزارش | نتیجه |
| --- | --- |
| Problems Confirmed | محتوای پیشنهادی پیش از اعتبارسنجی ذخیره می‌شد؛ پاسخ شکست، محتوای قبلاً تأییدشده را جایگزین می‌کرد؛ موفقیت ویرایش می‌توانست فیلدهای پشتیبانی‌نشده را هم به‌عنوان همگام ثبت کند؛ update/delete/publish قفل مشترک نداشتند. |
| Files Created | `tests/Feature/PublicationUpdateTest.php`. |
| Files Modified | `PublishingService`, `Publication`, `ChannelCapabilities`, قابلیت‌های ۹ درایور فعلی، migration مرحلهٔ ۰۵، ترجمه‌ها و مستندات hardening. پکیج میزبان با منبع همگام شد. |
| Changes Implemented | proposal روی clone اعتبارسنجی می‌شود؛ فیلدهای قابل ویرایش و پاک‌سازی برای هر درایور از ChannelCapabilities می‌آیند؛ تغییر نامعتبر/پشتیبانی‌نشده پیش از API رد می‌شود؛ نتیجهٔ موفق محتوای اصلی را در تراکنش با نسخه افزایش‌یافته ثبت می‌کند؛ شکست قطعی محتوای اصلی را نگه می‌دارد؛ UnknownOutcome پیشنهاد را در metadata حفظ می‌کند؛ update/delete یک attempt token مشترک می‌گیرند و publish در زمان قفل mutation ارسال نمی‌شود. |
| Database Changes | migration مرحلهٔ ۰۵ اکنون `content_version` با مقدار پیش‌فرض صفر نیز می‌افزاید؛ برای رکوردهای قبلی backfill لازم نیست. `pending_content`, `sync_status` و `last_synced_at` در metadata ذخیره می‌شوند. |
| Breaking Changes | تغییر فیلدهایی که درایور پشتیبانی نمی‌کند با خطای Unsupported رد می‌شود؛ برخی درایورها پاک‌کردن فیلد خالی را پشتیبانی نمی‌کنند و آن را رد می‌کنند. امضای عمومی update ثابت است. |
| Tests Added | پنج سناریو در `PublicationUpdateTest`: اعتبارسنجی بدون تغییر محتوای تأییدشده، شکست قطعی، موفقیت، نتیجه مبهم، محدودیت فیلد و رقابت mutation/نسخه. |
| Test Results | مجموعهٔ پکیج: 41 تست و 6446 assertion پاس؛ آزمون جدید مرحله: 5 تست و 31 assertion پاس؛ Pint فایل‌های تغییرکرده پاس شد. |
| Remaining Risks | شکست DB پس از موفقیت API، lock و proposal را برای بررسی بازیابی‌پذیر باقی می‌گذارد، اما تطبیق/رفع خودکار ندارد. رقابت چندپردازه و migration روی MySQL/PostgreSQL در STEP 09 لازم است. قابلیت‌های Update درایورهای واقعی بدون sandbox API آنلاین آزموده نشده‌اند. |
| Acceptance Criteria Status | شکست validation و API محتوای قبلی را حفظ می‌کند؛ موفقیت محتوای جدید و content_version را ثبت می‌کند؛ نتیجه مبهم موفقیت اعلام نمی‌شود و pending proposal می‌ماند؛ Update/Delete/Publish هم‌زمان با token و نسخه کنترل می‌شوند. |
- [ ] تغییر مرحله مستقل و قابل بازگشت باشد؛ پیش از عبور، معیارهای پذیرش بررسی شوند.

این موارد قالب مشترک مراحل آینده‌اند و تیک نخوردن آن‌ها به معنی ناقص بودن مستندسازی STEP 00 نیست. تکمیل کل مرحله اول مقاوم‌سازی فعلاً ادعا نمی‌شود.



## گزارش پایان STEP 06

| فیلد گزارش | نتیجه |
| --- | --- |
| Problems Confirmed | Update پیش از اعتبارسنجی محتوای موفق را بازنویسی می‌کرد و نتیجه API می‌توانست فیلدهای اعمال‌نشده را موفق اعلام کند. |
| Files Created | `tests/Feature/PublicationUpdateTest.php`. |
| Files Modified | `PublishingService`, `Publication`, `ChannelCapabilities`، ۸ درایور، migration، ترجمه‌ها و مستندات hardening. |
| Changes Implemented | proposal مستقل؛ اعتبارسنجی پیش از درخواست؛ فهرست فیلدهای قابل ویرایش/پاک‌کردن هر درایور؛ قفل مشترک Update/Delete؛ نسخه محتوا؛ حفظ محتوای تأییدشده در شکست قطعی/مبهم و ثبت proposal همگام‌نشده. |
| Database Changes | افزودن `content_version` با پیش‌فرض صفر به migration افزایشی Step05؛ rollback هر دو ستون جدید را حذف می‌کند. |
| Breaking Changes | تغییر فیلدهای اعلام‌نشده توسط capability اکنون پیش از API رد می‌شود؛ مصرف‌کنندگان باید نتیجه unsupported را مدیریت کنند. |
| Tests Added | پنج سناریو در یک فایل: validation، شکست قطعی، موفقیت و نسخه، نتیجه مبهم، capability و قفل عملیات. |
| Test Results | آزمون هدفمند: 5 تست و 31 assertion پاس؛ مجموعه کامل: 41 تست و 6446 assertion پاس. Pint فایل‌های تغییرکرده در اجرای قبلی پاس شد. |
| Remaining Risks | رقابت چندپردازه و شکست DB پس از موفقیت خارجی، تطبیق واقعی کانال‌ها و محدودیت دقیق API در STEP09 نیازمند integration هستند. |
| Acceptance Criteria Status | validation و شکست قطعی محتوای قبلی را حفظ می‌کنند؛ فقط موفقیت خارجی محتوای جدید را ثبت می‌کند؛ نتیجه مبهم موفق گزارش نمی‌شود. |

## پیشرفت STEP 07 — کار در حال انجام

| مورد | نتیجه فعلی |
| --- | --- |
| حذف آلبوم | Telegram/Bale کل شناسه‌ها را ذخیره و replay می‌کنند؛ حذف عضو‌به‌عضو است و retry فقط شناسه‌های باقی‌مانده را هدف می‌گیرد. آلبوم‌های قدیمی فقط با شناسه موجود قابل حذف‌اند. |
| غیرفعال‌سازی | قرارداد اختیاری و سرویس مستقل افزوده شد؛ Basalam و WhatsApp مسیر deactivation و log جدا دارند؛ Published و شناسه حفظ می‌شوند. |
| حفظ سابقه | migration پایه و migration upgrade قیدهای restrict و نگهداری log را تعریف می‌کنند. migration upgrade روی SQLite عمداً بی‌اثر است؛ سازگاری upgrade SQLite و اجرای MySQL/PostgreSQL به STEP 09 واگذار است. |
| اطلاعات کانال | ماتریس قابلیت و محدودیت ۹ درایور در `CHANNEL-CAPABILITIES.md` ثبت شد؛ موارد، snapshot قرارداد کدند و تأیید زنده API نیستند. |
| آزمون‌های مرحله | تست‌های WhatsApp batch پذیرش مبهم، انسداد mutation، endpoint بررسی وضعیت، ذخیرهٔ نتیجه بدون تغییر وضعیت انتشار و پاک‌سازی token را پوشش می‌دهند. مجموعهٔ کامل پکیج در اجرای نهایی: 57 تست و 7421 assertion پاس. |
| موارد باز | API و فرمان بررسی دستی batch فراهم است؛ poller صف و ثبت خودکار نتیجهٔ نهایی/error نیازمند رفتار تأییدشدهٔ per-item و شناسهٔ واقعی catalog است؛ آزمون upgrade پایگاه داده هدف به STEP 09 منتقل شد. قابلیت‌های آمادهٔ این مرحله تکمیل‌اند و این موارد به‌عنوان وابستگی/محدودیت ثبت شده‌اند. رابط میزبان برای اتصال دارای تاریخچه، قطع امن را به‌جای حذف انجام می‌دهد و آزمون هدفمند آن پاس شد. |

## پیشرفت STEP 08 — کار در حال انجام

| مورد | نتیجه فعلی |
| --- | --- |
| زمان انتظار و backoff | `Retry-After` عددی/تاریخی و بدنه Bot API parse می‌شود؛ مهلت هر job جدید در constructor ثابت می‌شود؛ backoff خالی/نامعتبر fallback امن دارد و jitter مثبت اعمال می‌شود. |
| dispatch زمان‌بندی‌شده | reservation cache بر مبنای publication و زمان برنامه از dispatch تکراری جلوگیری می‌کند؛ job پیش از ارسال موعد تازه را بازبینی می‌کند. نیاز به cache مشترک و آزمون backend عملیاتی باقی است. |
| تمدید token | Instagram و Basalam از قفل cache بر اساس connection استفاده و پس از قفل اعتبارنامه را تازه می‌خوانند؛ آزمون contention و جلوگیری از refresh تکراری افزوده شد. |
| rate limit واقعی | هنوز middleware در سطح job و با کلید driver است؛ درخواست مستقیم و چندمرحله‌ای هرکدام جدا شمرده نمی‌شوند. مقدارهای داخلی کانال‌ها در `CHANNEL-CAPABILITIES.md` مستند شده‌اند و quota رسمی فرض نشده‌اند. |
| تست‌ها | `QueueRetryPolicyTest`, `ScheduledPublicationDispatchTest`, `TokenRefreshConcurrencyTest`: header/body، deadline ثابت پس از serialization، backoff خالی، dispatch تکراری، تغییر موعد و contention تمدید token. مجموعهٔ کامل پکیج: 57 تست و 7421 assertion پاس. |
| موارد باز | Retry-After در همه driverها، نرخ‌دهی اتمیک هر درخواست به‌ازای حساب، پایداری reservation/lock در cache عملیاتی و بودجهٔ کل عملیات برای STEP 09 یا فاز اتصال API واقعی ثبت شده‌اند؛ اجرای نهایی پکیج موفق و ۲۲ فایل تغییر‌یافته با میزبان hash-verified هستند. |

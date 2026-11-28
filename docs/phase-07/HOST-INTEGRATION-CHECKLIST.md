# چک‌لیست یکپارچگی میزبان (G7-01)

استفاده در نصب تازه، ارتقا، و پیش از فعال‌سازی catalog_sync در تولید.

## نصب و پایگاه داده

- [ ] PHP 8.4.1+ و Laravel 13 / Illuminate 13
- [ ] `composer require karnoweb/omnichannel` روی نسخه مصوب
- [ ] migrate پکیج بدون خطا در همان موتور DB تولید
- [ ] config منتشرشده بررسی شده (یا مقادیر env)

## Binding میزبان

- [ ] `BranchContextResolverContract` متصل و در درخواست‌های omnichannel شعبه تنظیم می‌شود
- [ ] `ProductRepository` شاخه‌ای برای انتشار Product
- [ ] در صورت Reference: حداقل یک `PublicationSourceResolverContract` در `PublicationSourceRegistry`
- [ ] رسانه: storage/URL resolver در صورت غیر local

## صف و زمان‌بندی

- [ ] worker صف برای connection queue پیکربندی شده
- [ ] `omnichannel:dispatch-due` در cron
- [ ] `omnichannel:doctor` یا `health` دوره‌ای (بدون افشای credential در لاگ عمومی)

## catalog_sync (فقط اگر فعال)

- [ ] `catalog_sync.enabled` عمداً true شده
- [ ] هر اتصال هدف: `metadata.catalog_sync_enabled`
- [ ] رویداد تغییر قیمت/موجودی میزبان به trigger متصل
- [ ] تست غیرفعال بودن sync (بدون HTTP اضافی) در staging

## batch و idempotency

- [ ] `intent_id` پایدار از فرم batch
- [ ] transaction میزبان با dispatch پس از commit سازگار

## ارتقا

- [ ] مطابق [UPGRADING.md](../../UPGRADING.md)
- [ ] worker قبل از migrate متوقف یا خالی شده
- [ ] پس از ارتقا: suite میزبان + `php artisan omnichannel:doctor`

## خارج از مخزن پکیج (ثبت نتیجه میزبان)

- [ ] UI انتشار و مجوزها
- [ ] سناریوی end-to-end یک کانال در staging
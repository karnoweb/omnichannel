# PROVIDER-DEVELOPMENT-STANDARD

## پیش از تغییر درایور

- [ ] شناسه نیازمندی / WP
- [ ] مستند رسمی + تاریخ در [`docs/providers/`](../../providers/)
- [ ] ماتریس قابلیت step-03
- [ ] نوع حساب و مجوز (بدون ذخیره secret در repo)

## پیاده‌سازی

- `capabilities()` صادقانه — عملیات پشتیبانی‌نشده exception/flag مشخص
- احراز هویت و refresh توکن طبق کانال
- خطا: map به exception داخلی؛ بدون افشای token در message
- rate limit: `BaseApiClient` — تغییر scope connection را regression بگیر

## رسانه و کاتالوگ

- اعتبارسنجی URL/اندازه — [`SECURITY-STANDARD.md`](SECURITY-STANDARD.md)
- Basalam/WA: مالکیت قیمت/موجودی میزبان
- همگام‌سازی فقط opt-in

## سطح پشتیبانی

طبق [PROVIDER-SUPPORT-POLICY](../step-04/PROVIDER-SUPPORT-POLICY.md): تفکیک رسمی / پیاده‌شده / mock / live.

**Demo:** فقط تست — در وضعیت کانال واقعی حساب نشود.

## تغییر API خارجی

1. ثبت شاهد رسمی
2. به‌روز providers + CHANGELOG
3. بسته کار ROADMAP
4. بدون تصمیب مالک، رفتار جدید production فعال نشود

## چک‌لیست بازبینی درایور

- [ ] تست contract در `tests/Drivers/` یا feature مرتبط
- [ ] مستند فارسی provider
- [ ] نمونه درخواست/پاسخ **ساختگی**
- [ ] محدودیت نرخ و UnknownOutcome مستند
# الزامات میزبان — PHP و Illuminate

## نسخه‌های مصوب (DEC-007)

مطابق [composer.json](../../composer.json) پکیج:

| مؤلفه | نسخه |
|-------|------|
| PHP | `^8.4.1` |
| `illuminate/console`, `contracts`, `database`, `events`, `http`, `queue`, `support` | `^13.0` |

میزبان باید Laravel 13 (یا مصرف مستقیم کامپوننت‌های Illuminate 13 هم‌نسخه) را اجرا کند. نسخه‌های PHP 8.3 و زیر **پشتیبانی نمی‌شوند** برای نصب/ارتقای این خط پکیج.

## توسعه پکیج (نه میزبان)

- `orchestra/testbench` `^11.0` برای PHPUnit در خود مخزن omnichannel.

## بررسی سریع میزبان

```bash
php -v
php artisan --version
composer show karnoweb/omnichannel
```

## ارتباط با UPGRADING

جدول مسیر ارتقا: [UPGRADING.md](../../UPGRADING.md) — ستون PHP/Laravel با این سند هم‌تراز شده است.

## نصب تازه

1. اطمینان از PHP 8.4.1+
2. نصب پکیج و migrate
3. تکمیل [HOST-INTEGRATION-CHECKLIST.md](HOST-INTEGRATION-CHECKLIST.md)
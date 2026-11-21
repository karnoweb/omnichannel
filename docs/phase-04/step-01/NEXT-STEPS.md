# NEXT-STEPS

## مرحله ۴.۲ — سرویس‌دهندگان

| اولویت | موضوع | وابستگی |
|---|---|---|
| P1 | Instagram Graph + OAuth URLs | [DRIVER-AUD.md](DRIVER-AUD.md) |
| P1 | Basalam OpenAPI taxonomy/product/attributes | OMC-AUD-011 |
| P1 | WhatsApp Catalog batch API | whatsapp driver |
| P2 | Rubika/Gap/Eitaa bot API docs | Messaging clients |
| P2 | Telegram/Bale official limits | BotApiClient |

برای هر سرویس: وب‌سایت رسمی، نسخه API، auth، rate limit، webhooks — با تاریخ اعتبارسنجی.

## اصلاحات پیشنهادی (از یافته‌ها)

| شناسه | اقدام | اولویت |
|---|---|---|
| OMC-AUD-007 | تست Feature ارتقا DB تا migrationهای 2026_11_21 | P1 |
| OMC-AUD-009 | job CI MySQL یا مستند Integration Required | P1 |
| OMC-AUD-002 | سند سازگاری Illuminate 13 + Testbench 11 | P1 |
| OMC-AUD-001 | به‌روزرسانی composer.lock | P2 |
| OMC-AUD-005 | تکمیل `docs/phase-03/FINAL-REPORT.md` | P2 |
| OMC-AUD-003 | حذف `\|\| true` از audit CI یا artefact | P2 |

## تصمیم‌های مالک محصول

1. آیا Operational gate بدون karno-base کافی است برای برچسب «فاز ۳ تمام»؟
2. فعال‌سازی catalog sync در چه محیطی مجاز است؟
3. Reference source (بلاگ) در فاز بعدی یا خارج از محصول؟

## شروع ۴.۲

پس از تأیید مالک، پرسش‌های `DRIVER-AUD.md` را به backlog `docs/providers/` منتقل کنید. **بدون** شروع توسعه یا scrape خودکار در ۴.۱.
# PROVIDER-ROADMAP

مرجع فنی: [`docs/providers/`](../../providers/). سیاست سطح: [`step-04/PROVIDER-SUPPORT-POLICY`](../step-04/PROVIDER-SUPPORT-POLICY.md).

| درایور | سطح فعلی | هدف | WPها | ریسک |
|---------|-----------|-----|------|------|
| instagram | B | B→A | WP-05-02, WP-05-03, WP-08-05 | Graph breaking |
| telegram | A | A | WP-05-07, WP-08-05 | rate مشترک Bale |
| whatsapp-catalog | B | B | WP-05-05, WP-06-04 | UnknownOutcome |
| basalam | A/B | A | WP-06-01, WP-06-03 | status IDs |
| bale | A/B | A | WP-05-04, WP-05-07 | BotApiClient |
| rubika | C | C یا B¹ | WP-05-08, WP-DEC-005 | doc ناقص |
| gap | C | C یا B¹ | WP-05-08 | همان |
| eitaa | C | مشروط² | WP-DEC-002 | درگاه ثالث |
| demo | D | D | — | غیر production |

¹ DEC-005. ² DEC-002.

## Instagram

- **شکاف:** P43-GAP-002,003
- **تست:** mock موجود؛ live WP-08-05
- **مستند:** `providers/instagram.md` پس از هر تغییر Graph/rate

## Telegram / Bale

- **شکاف:** P43-GAP-005
- **عملیات ضروری:** message, album — ماتریس step-03

## WhatsApp Catalog

- **شکاف:** batch reconciliation، بدون messaging
- **مرجع:** REL-CHANGE-003، hardening

## Basalam

- **شکاف:** P43-GAP-004 — deactivate vs delete
- **کاتالوگ:** همراه WP-06-02

## Rubika / Gap

- **وضعیت شواهد:** عدم پشتیبانی / پیاده‌سازی / دسترسی — **شواهد ضعیف**
- **اقدام:** تحقیق محدود؛ بدون ادعای Production Verified

## Eitaa

- **وضعیت:** مسدود تا DEC-002
- **گزینه:** deprecate درایور یا سطح C ثابت با هشدار

## Demo

- فقط تست محلی؛ در نقشه live و فاز ۹ **نامربوط**.
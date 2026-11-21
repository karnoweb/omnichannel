# DECISIONS-AND-BLOCKERS

## تصمیم‌های باز (از ۴.۴ / ۴.۷)

| DEC | موضوع | اثر بر توسعه | قبل از |
|-----|--------|--------------|--------|
| DEC-001 | نرخ IG | WP-05-02 مسدود | IG تغییرات |
| DEC-002 | Eitaa | WP-DEC-002 | سطح درایور |
| DEC-003 | catalog_sync | پیشنهاد off | WP-06-02 enable |
| DEC-004 | WA messaging | catalog only | messaging |
| DEC-005 | Rubika/Gap | WP-05-08 | ادعای B |
| DEC-006 | Graph | WP-05-03 | bump IG |
| DEC-007 | Illuminate میزبان | WP-07-04 | سند نصب |
| DEC-008 | Webhook | تعویق | webhook code |

## موانع فاز ۴

| مانع | مسئول | کار مستقل مجاز |
|------|--------|----------------|
| PRD draft | مالک | مستند |
| Git tag ناقص | مالک/انتشار | — |
| MySQL CI نبود | DevOps WP-08-01 | تست محلی SQLite |
| live مجوز | مالک | mock |

## شروع فاز ۵

**مسدود** تا: پذیرش مشروط رفع شود + دستور مالک + DoR WP-05-01.

## استثناءهای CI

`composer audit || true` — پیشنهاد سخت‌گیری آینده، نه در ۴.۸.
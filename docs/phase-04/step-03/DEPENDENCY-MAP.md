# DEPENDENCY-MAP

## وابستگی اقدام‌ها

| اقدام | پیش‌نیاز | نوع | شرط رفع |
|---|---|---|---|
| اصلاح Graph v27 | P43-GAP-003 | فنی/رسمی | بررسی breaking Meta |
| Basalam status | P43-GAP-004 | رسمی | پاسخ باسلام |
| Instagram rate policy | P43-GAP-002 | محصولی | P43-DEC-001 |
| Bale parity | P43-GAP-005 | فنی | P42-Q-009 |
| Rubika/Gap doc | P43-GAP-010,011 | رسمی | شواهد جدید P43-EV |
| Eitaa policy | P43-GAP-012 | محصولی/حقوقی | P43-DEC-002 |
| Upgrade test 13.3 | P43-GAP-007 | فنی | migrations 2026_11_21 |
| MySQL CI | P43-GAP-008 | عملیاتی | pipeline |
| Catalog sync فعال | OMC-CORE-007 | محصولی | تصمیم میزبان + P43-DEC-003 |
| Host integration | P43-GAP-014 | میزبان | خارج پکیج |

## تصمیم‌های باز

- P43-DEC-001: سیاست سقف Instagram (هم‌راستا P42-Q-003)
- P43-DEC-002: ادامه پشتیبانی Eitaa vs API رسمی
- P43-DEC-003: پیش‌فرض `catalog_sync.enabled` در محصول

## گروه‌بندی پیشنهادی (بدون شماره فاز نهایی)

1. **شواهد و قرارداد:** 010, 011, 012, 003, 004
2. **تست و پلتفرم:** 006, 007, 008, 009
3. **مستندات مالک:** 015, 001
4. **میزبان:** 014, 013
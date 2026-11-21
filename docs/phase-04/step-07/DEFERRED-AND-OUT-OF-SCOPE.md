# DEFERRED-AND-OUT-OF-SCOPE

## خارج از دامنه نسخه پایدار (PRD غیرهدف)

| مورد | دلیل | شرط بازنگری |
|------|------|-------------|
| UI میزبان (inbox، داشبورد) | P43-GAP-014 | تصمیم محصول جدا |
| Webhook دریافت رخداد | DEC-008 باز | مصوب PRD + ADR |
| CRM / messaging عمومی WA | DEC-004 catalog-only | تصمیم مالک |
| پوشش ۱۰۰٪ API رسمی هر کانال | PROVIDER policy | فقط سطح A هدف |

## تعویق مشروط

| مورد | شناسه | شرط |
|------|--------|------|
| Eitaa تولید | DEC-002, WP-DEC-002 | شواهد حقوقی/API |
| Reference registrar کامل | P43-GAP-013 | نیاز میزبان MVP |
| taxonomy live | P43-GAP-009 | staging + مجوز |
| composer.lock در repo | P43-GAP-001 | سیاست انتشار library |

## Demo

درایور `Demo`: آزمایش و CI فقط — **هرگز** ادعای کانال واقعی.

## پذیرش ریسک موقت (نیاز تصویب)

- Rubika/Gap سطح C تا DEC-005
- Redis production بدون تست گروه redis
- ارتقای چندپله 13.0→13.3 بدون تست میانی (REL-DEC-004)
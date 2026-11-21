# RELEASE-WORKFLOW

سیاست مرجع: [docs/releases/RELEASE-POLICY.md](../../releases/RELEASE-POLICY.md)، قالب: [CHANGE-ENTRY-TEMPLATE.md](../../releases/CHANGE-ENTRY-TEMPLATE.md)، چک‌لیست: [RELEASE-CHECKLIST.md](../../releases/RELEASE-CHECKLIST.md).

## حین توسعه

- تغییر رفتاری → بخش `Unreleased` در [CHANGELOG.md](../../../CHANGELOG.md)
- شناسه: `REL-CHANGE-*` یا الگوی releases
- breaking → UPGRADING + تصمیم مالک

## قبل از نامزد انتشار

1. RELEASE-CHECKLIST کامل
2. TEST-MATRIX ردیف‌های الزامی برای آن دامنه
3. QUALITY-GATES فاز مربوط
4. DEC بسته یا پذیرش ریسک
5. tag/version — **فقط مرحله مجاز آینده** با اختیار انتشار

## تفکیک مفاهیم

| مفهوم | معنی |
|--------|------|
| پایان کد | merge + DoD |
| پذیرش WP | maintainer |
| پذیرش فاز | چک‌لیست فاز |
| آمادگی انتشار | RELEASE + G8/G9 |
| عرضه | tag/Packagist — تصمیم مالک |
| Production Verified | جدا — WP-09-03 |

## SemVer

طبق RELEASE-POLICY پیشنهادی — **مصوب نشده**.

## پس از انتشار

ثبت محدودیت کانال/محیط؛ پشتیبانی نسخه — تصمیم مالک.
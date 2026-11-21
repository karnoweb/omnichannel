# MEDIA-ARCHITECTURE

## As-Is

1. میزبان آپلود → `PublicationMediaService` metadata
2. URI `omc-asset:{id}` در content
3. publish: `PublicationMediaStorageContract` → bytes → درایور upload/send

Legacy: `MediaItem` URL — `HttpMediaBytesResolver` با SSRF guard.

## چرخه

`PrunePublicationMediaCommand` — pruning assets.

## To-Be

**حفظ** تصمیم phase-03: پیش‌فرض نه HTTP عمومی برای asset.

To-Be اختیاری: سیاست TTL و quota میزبان (خارج پکیج).
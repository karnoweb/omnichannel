# EVIDENCE-REGISTRY — شناسه‌ها و ارجاع

## نگاشت قابلیت مشترک (`OMC-CAP-*`)

| شناسه | رفتار | خانواده |
|---|---|---|
| OMC-CAP-001 | اتصال / اعتبارسنجی توکن | اتصال |
| OMC-CAP-002 | انتشار متن (پست یا پیام) | انتشار |
| OMC-CAP-003 | انتشار تصویر | انتشار / رسانه |
| OMC-CAP-004 | انتشار ویدئو | انتشار / رسانه |
| OMC-CAP-005 | آلبوم / کاروسل چندرسانه‌ای | رسانه |
| OMC-CAP-006 | Reel / Story (فید اجتماعی) | انتشار |
| OMC-CAP-007 | محصول کاتالوگ / لیستینگ | کاتالوگ |
| OMC-CAP-008 | به‌روزرسانی قیمت و موجودی | کاتالوگ |
| OMC-CAP-009 | ویرایش محتوای منتشرشده | چرخه عمر |
| OMC-CAP-010 | حذف | چرخه عمر |
| OMC-CAP-011 | غیرفعال‌سازی (جایگزین حذف) | چرخه عمر |
| OMC-CAP-012 | زمان‌بندی (هسته پکیج، نه API بومی) | انتشار |
| OMC-CAP-013 | هشتگ / کلمات کلیدی | ویژگی |
| OMC-CAP-014 | متن جایگزین تصویر (alt) | ویژگی |
| OMC-CAP-015 | Thumbnail / کاور ویدئو | ویژگی |
| OMC-CAP-016 | ارسال بی‌صدا | ویژگی |
| OMC-CAP-017 | پین پیام | ویژگی |
| OMC-CAP-018 | Webhook / رخداد رسمی | رخدادها |
| OMC-CAP-019 | محدودیت نرخ / صف | پایداری |
| OMC-CAP-020 | همگام‌سازی کاتالوگ دوطرفه | همگام‌سازی |

## هسته (`OMC-CORE-*`)

| شناسه | موضوع | شواهد اولیه |
|---|---|---|
| OMC-CORE-001 | ثبت درایور / `ChannelManager` | `src/OmnichannelServiceProvider.php` |
| OMC-CORE-002 | منابع انتشار Product / FreeForm | `PublicationCreationService` |
| OMC-CORE-003 | منبع Reference (registrar) | OMC-AUD-006 |
| OMC-CORE-004 | انتشار گروهی | `PublicationBatchService` |
| OMC-CORE-005 | دارایی رسانه `omc-asset:` | `PublicationMediaService` |
| OMC-CORE-006 | نگاشت taxonomy | `MarketplaceTaxonomyService` |
| OMC-CORE-007 | Catalog sync (پیش‌فرض off) | OMC-AUD-010, `CatalogSyncPolicy` |
| OMC-CORE-008 | Reconcile WhatsApp | `WhatsAppCatalogReconciliationService` |
| OMC-CORE-009 | Rate limiter | `ApiRequestRateLimiter` |
| OMC-CORE-010 | SSRF محافظت رسانه | `HttpMediaBytesResolver` |
| OMC-CORE-011 | صف انتشار | `PublishPublicationJob` |
| OMC-CORE-012 | مرز میزبان (قیمت/موجودی) | `CatalogSyncPolicy`, مستندات host |

## نمونه ردیف‌ها (`P43-ROW-*`)

| P43-ROW | OMC-CAP | درایور | CAP/OP رسمی | یافته مرتبط |
|---|---|---|---|---|
| P43-ROW-001 | OMC-CAP-002 | instagram | IG-CAP-001 | P42-DIFF-002 |
| P43-ROW-002 | OMC-CAP-005 | instagram | IG-CAP-002 | P42-DIFF-001 |
| P43-ROW-003 | OMC-CAP-002 | telegram | TG-CAP-001 | — |
| P43-ROW-004 | OMC-CAP-005 | telegram | TG-CAP-002 | TG-OP-003 |
| P43-ROW-005 | OMC-CAP-007 | whatsapp | WA-CAP-001 | WA-OP-001 |
| P43-ROW-006 | OMC-CAP-008 | whatsapp | WA-CAP-003 | — |
| P43-ROW-007 | OMC-CAP-007 | basalam | BS-CAP-002 | P42-DIFF-003 |
| P43-ROW-008 | OMC-CAP-002 | bale | BL-CAP-001 | P42-DIFF-005 |
| P43-ROW-009 | OMC-CAP-002 | rubika | RB-CAP-001 | P42-DIFF-006 |
| P43-ROW-010 | OMC-CAP-002 | gap | GP-CAP-001 | P42-DIFF-007 |
| P43-ROW-011 | OMC-CAP-002 | eitaa | ET-CAP-001 | P42-DIFF-008 |

(ردیف‌های کامل در [drivers/](drivers/) و [CAPABILITY-MATRIX.md](CAPABILITY-MATRIX.md).)

## شواهد تکمیلی (`P43-EV-*`)

| شناسه | نوع | توضیح | پیوند |
|---|---|---|---|
| P43-EV-001 | تست | PHPUnit 102 OK، SQLite | [WORKLOG.md](WORKLOG.md) |
| P43-EV-002 | سورس | enum `Capability` | `src/Enums/Capability.php` |
| P43-EV-003 | مرجع ۴.۲ | اختلاف‌های مستند | [../step-02/VERIFIED-DISCREPANCIES.md](../step-02/VERIFIED-DISCREPANCIES.md) |

## تعارض‌های شناخته‌شده

| موضوع | شواهد A | شواهد B | وضعیت |
|---|---|---|---|
| سقف نرخ Instagram | Meta 100/24h [IG-S001] | داخلی 25/h | P42-DIFF-001، نیاز تصمیم P42-Q-003 |
| Graph نسخه | مثال v27 رسمی | v26.0 پکیج | P42-DIFF-002، P42-Q-006 |
| Basalam status | نامشخص رسمی | 2976/3790 env | P42-DIFF-003 |
| Bale vs Telegram | API مستقل بله | `BotApiClient` مشترک | P42-DIFF-005 |

## منابع ناقص

- Rubika RB-S001 — پرتال محدود (P42-Q-010)
- Gap GP-S001 — مستند رسمی جزئی (P42-Q-011)
- Eitaa ET-S001 — درگاه ثالث (P42-Q-012)
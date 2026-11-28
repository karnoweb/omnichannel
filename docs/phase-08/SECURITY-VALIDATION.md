# اعتبارسنجی امنیت (WP-08-03 / OMC-AUD-002)

| سناریو | شاهد |
|--------|------|
| دسترسی cross-branch | [BranchIsolationTest](../../tests/Feature/BranchIsolationTest.php), [MediaAssetBranchIsolationTest](../../tests/Feature/MediaAssetBranchIsolationTest.php) |
| لاگ انتشار بدون نشت payload حساس | [PublicationLoggerSecurityTest](../../tests/Feature/PublicationLoggerSecurityTest.php) |
| پاسخ API | [ApiResponseSecurityTest](../../tests/Unit/Http/ApiResponseSecurityTest.php) |
| resolver رسانه HTTP | [HttpMediaBytesResolverSecurityTest](../../tests/Feature/HttpMediaBytesResolverSecurityTest.php) |
| redact تاریخچه | [RedactSensitiveHistoryCommandTest](../../tests/Feature/RedactSensitiveHistoryCommandTest.php) |
| doctor بدون credential | [OmnichannelDoctorSanitizationTest](../../tests/Feature/OmnichannelDoctorSanitizationTest.php) |

## عملیات

`omnichannel:redact-history` — dry-run قبل از `--apply` در تولید.

## خارج از پکیج

سیاست نقش میزبان، WAF، و چرخش توکن OAuth در اپلیکیشن میزبان.
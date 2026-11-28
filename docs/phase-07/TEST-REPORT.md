# گزارش آزمون — فاز ۷

| اجرا | نتیجه |
|------|--------|
| `vendor/bin/phpunit --exclude-group redis` | OK — 157 tests, 14594 assertions |
| Redis group | اختیاری با `OMNICHANNEL_TEST_REDIS_HOST` |

تست‌های جدید:

- `PublicationSourceValidationTest::test_reference_source_with_registered_resolver_creates_publication`
- `ChannelManagerExtendTest`
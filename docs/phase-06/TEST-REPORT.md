# نتایج آزمون فاز ۶

PHP 8.4.22، PHPUnit 13.4.7.

| فرمان | نتیجه |
|---|---|
| `vendor/bin/phpunit --exclude-group redis` | ۱۵۵ تست، ۰ شکست |
| گروه CatalogSync* + MarketplaceTaxonomyBasalamTest | شامل شده در مجموعه |

Redis: همان روش فاز ۵.۹ با `OMNICHANNEL_TEST_REDIS_HOST`.
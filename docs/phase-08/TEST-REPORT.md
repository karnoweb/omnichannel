# گزارش آزمون — فاز ۸

| اجرا | نتیجه |
|------|--------|
| `vendor/bin/phpunit --exclude-group redis` | OK — 158 tests, 14653 assertions |
| `--group redis` | با `OMNICHANNEL_TEST_REDIS_HOST` |
| MySQL | job GitLab `phpunit-mysql` — شاهد pipeline مشروط |

تست جدید: `OmnichannelDoctorSanitizationTest`.
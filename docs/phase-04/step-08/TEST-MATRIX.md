# TEST-MATRIX

| ID | نیازمندی/WP | PHP | محیط | نوع | الزام | فرمان | وضعیت | شاهد | مانع |
|----|-------------|-----|------|-----|--------|--------|--------|------|------|
| TM-01 | suite پایه | 8.3+ | SQLite mem | Feature/Unit | هر PR | `phpunit --exclude-group redis` | تاریخی OK | step-01 | — |
| TM-02 | FR-038 | 8.3 | SQLite | Upgrade | فاز ۵ | UpgradeFrom130* | ناقص | OMC-AUD-007 | WP-05-01 |
| TM-03 | Redis rate | 8.3 | Redis | Integration | فاز ۸ | `--group redis` | اجرا نشده | — | Redis |
| TM-04 | NFR-010 | 8.3 | MySQL | Migration CI | فاز ۸ | pipeline آینده | مسدود | — | WP-08-01 |
| TM-05 | NFR-010 | 8.3 | PostgreSQL | Migration | فاز ۸ | دستی | مسدود | — | WP-08-02 |
| TM-06 | میزبان | IL 13 | host app | Integration | خارج پکیج | host suite | مسدود | OMC-AUD-012 | WP-07-02 |
| TM-07 | live IG/TG/BS | 8.3 | staging | Real provider | فاز ۸ | @group live | مسدود | — | مجوز |
| TM-08 | Architecture | 8.3 | SQLite | Architecture | هر PR | tests/Architecture | تاریخی part of suite | step-01 | — |
| TM-09 | Pint | 8.3 | — | Style | CI | `pint --test` | در CI تعریف شده | .gitlab-ci.yml | — |
| TM-10 | Composer validate | — | CI | Meta | CI | validate --strict | در CI | .gitlab-ci.yml | — |

## سناریوهای ریسک (پوشش هدف)

جداسازی شعب؛ idempotency؛ UnknownOutcome WA؛ batch جزئی؛ catalog off؛ media URL؛ token refresh.

ارجاع شکاف: step-03 VALIDATION-MATRIX.

**تذکر:** موفقیت TM-01 به‌تنهایی Production Verified نیست.
# BASELINE-AND-INPUTS

## نسخه و وابستگی (از `composer.json`)

| مورد | قید |
|------|-----|
| پکیج | `karnoweb/omnichannel` @ `13.3.0` |
| PHP | `^8.3` |
| Illuminate | `^13.0` |
| Orchestra Testbench | `^11.0` |
| PHPUnit | `^11.0\|^12.0\|^13.0` |
| Laravel Pint | `^1.18` |
| Scripts | `composer test` → phpunit؛ `composer pint` |

**شکاف مستند‌شده:** Testbench 11 با Illuminate 13 — P43-GAP-006، DEC-007 (میزبان و dev).

## PHPUnit (`phpunit.xml`)

- یک testsuite: کل پوشه `tests/`
- bootstrap: `vendor/autoload.php`
- **بدون** تعریف `<groups>` در xml — استثنای redis با CLI: `--exclude-group redis`

## CI (`.gitlab-ci.yml` — فقط خوانده شده)

| مرحله | محتوا |
|--------|--------|
| image | `php:8.3-cli` |
| extensions | zip, pdo_sqlite |
| script | `pint --test`؛ `phpunit --exclude-group redis`؛ `composer validate --strict`؛ `composer audit \|\| true` |

**شکاف هدف:** `audit \|\| true` مسدودکننده نیست؛ MySQL/Redis در CI نیست (NFR-010، WP-08-01).

## ورودی‌های مراحل ۴.۱–۴.۷

همه در مسیرهای `docs/phase-04/step-0*`، `PRD.md`, `ARCHITECTURE.md`, `ROADMAP.md`, `CHANGELOG.md`, `UPGRADING.md` — بدون بازنویسی در ۴.۸.

## PRD / تصمیم

- PRD: 0.1-draft — مصوب نشده
- DEC-001..008: باز — [`step-07/DECISIONS.md`](../step-07/DECISIONS.md)
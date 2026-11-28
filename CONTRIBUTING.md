# راهنمای مشارکت و توسعه — karnoweb/omnichannel

نسخه سند: 0.1-draft (مرحله ۴.۸). **تکمیل این فایل شروع خودکار فاز ۵ نیست.**

## نقشه خواندن

| سند | کاربرد |
|-----|--------|
| [README.md](README.md) | نصب و overview |
| [PRD.md](PRD.md) | دامنه محصول (پیش‌نویس) |
| [ARCHITECTURE.md](ARCHITECTURE.md) | معماری هدف |
| [ROADMAP.md](ROADMAP.md) | بسته‌های کار `WP-*` |
| [docs/providers/](docs/providers/) | سرویس‌دهندگان |
| [CHANGELOG.md](CHANGELOG.md) / [UPGRADING.md](UPGRADING.md) | نسخه و ارتقا |

جزئیات استاندارد: [docs/phase-04/step-08/README.md](docs/phase-04/step-08/README.md).

## شروع سریع محیط

1. PHP 8.4.1+، Composer 2.x، extensionهای zip و sqlite (برای تست پیش‌فرض)
2. `composer install`
3. `vendor/bin/phpunit --exclude-group redis`
4. `vendor/bin/pint --test`

**CI رسمی:** [GitHub Actions](.github/workflows/ci.yml) روی `main` و pull request (PHPUnit، Pint، validate، audit، MySQL، Redis). فایل [`.gitlab-ci.yml`](.gitlab-ci.yml) اختیاری/legacy.

### MySQL (اختیاری)

همان suite روی MySQL با متغیرهای env (مشابه CI job `phpunit-mysql`):

```bash
export OMNICHANNEL_TEST_MYSQL_HOST=127.0.0.1
export OMNICHANNEL_TEST_MYSQL_DATABASE=omnichannel_test
export OMNICHANNEL_TEST_MYSQL_USER=root
export OMNICHANNEL_TEST_MYSQL_PASSWORD=secret
vendor/bin/phpunit --exclude-group redis
```

جزئیات: [MIGRATION-VALIDATION.md](docs/phase-08/MIGRATION-VALIDATION.md).

راهنمای کامل: [DEVELOPMENT-SETUP.md](docs/phase-04/step-08/DEVELOPMENT-SETUP.md).

## انتخاب کار

- فقط بسته‌های دارای شناسه در [WORK-PACKAGES.md](docs/phase-04/step-07/WORK-PACKAGES.md) / [ROADMAP.md](ROADMAP.md)
- قبل از کد: [معیار آمادگی (DoR)](docs/phase-04/step-08/DEFINITION-OF-READY-AND-DONE.md)
- تصمیم‌های باز DEC-001..008 می‌توانند کار را **مسدود** کنند — [DECISIONS-AND-BLOCKERS.md](docs/phase-04/step-08/DECISIONS-AND-BLOCKERS.md)

## چرخه کار

[WORKFLOW.md](docs/phase-04/step-08/WORKFLOW.md) — انتخاب → طراحی → پیاده‌سازی (آینده) → آزمون → بازبینی → تحویل → پذیرش.

## استانداردهای اجباری

| موضوع | سند |
|--------|-----|
| کد و معماری | [CODING-AND-ARCHITECTURE-STANDARDS.md](docs/phase-04/step-08/CODING-AND-ARCHITECTURE-STANDARDS.md) |
| درایور | [PROVIDER-DEVELOPMENT-STANDARD.md](docs/phase-04/step-08/PROVIDER-DEVELOPMENT-STANDARD.md) |
| امنیت | [SECURITY-STANDARD.md](docs/phase-04/step-08/SECURITY-STANDARD.md) |
| آزمون | [TESTING-STANDARD.md](docs/phase-04/step-08/TESTING-STANDARD.md) + [TEST-MATRIX.md](docs/phase-04/step-08/TEST-MATRIX.md) |
| مستندات فارسی | [DOCUMENTATION-STANDARD.md](docs/phase-04/step-08/DOCUMENTATION-STANDARD.md) |
| انتشار | [RELEASE-WORKFLOW.md](docs/phase-04/step-08/RELEASE-WORKFLOW.md) + [docs/releases/](docs/releases/README.md) |
| CI و دروازه کیفیت | [CI-AND-QUALITY-GATES.md](docs/phase-04/step-08/CI-AND-QUALITY-GATES.md) |

## چهار الزام مالک (همیشگی)

| الزام | اجرا در هر PR آینده |
|--------|---------------------|
| CHANGELOG/ارتقا | `CHANGELOG.md`, `UPGRADING.md` برای تغییر رفتاری |
| مستند سرویس | `docs/providers/<driver>.md` |
| فارسی فنی | به‌روز همراه تغییر |
| نقشه راه | به‌روزرسانی `ROADMAP` / WP وضعیت با شاهد |

## پایان کار (DoD)

[DEFINITION-OF-READY-AND-DONE.md](docs/phase-04/step-08/DEFINITION-OF-READY-AND-DONE.md).

## قالب‌ها

[TEMPLATES.md](docs/phase-04/step-08/TEMPLATES.md) — پرامپت پیاده‌سازی: [IMPLEMENTATION-STEP-PROMPT-TEMPLATE.md](docs/phase-04/step-08/IMPLEMENTATION-STEP-PROMPT-TEMPLATE.md).

## پایان فاز ۴

[PHASE-04-CLOSURE.md](docs/phase-04/step-08/PHASE-04-CLOSURE.md) — ورود فاز ۵ مشروط: [PHASE-05-ENTRY.md](docs/phase-04/step-08/PHASE-05-ENTRY.md).

## گزارش باگ و امنیت

بدون توکن در issue. آسیب‌پذیری: گزارش خصوصی به maintainer (فرآیند تماس توسط مالک تعیین می‌شود — در مخزن ثبت نشده).

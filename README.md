# karnoweb/omnichannel

Laravel package for multi-channel publication (9 drivers). Version **13.4.1** (CI-stable patch on 13.4.0 line; catalog sync, host docs, GitHub Actions).

## Requirements

- PHP ^8.4.1
- Laravel ^13

## Install

```bash
composer require karnoweb/omnichannel
php artisan vendor:publish --tag=omnichannel-config
php artisan migrate
```

Release notes: [CHANGELOG.md](CHANGELOG.md), [UPGRADING.md](UPGRADING.md), [docs/releases/](docs/releases/README.md). Host integration: [docs/phase-07/HOST-INTEGRATION.md](docs/phase-07/HOST-INTEGRATION.md). Roadmap: [ROADMAP.md](ROADMAP.md). Contributing: [CONTRIBUTING.md](CONTRIBUTING.md) (CI: GitHub Actions).

## Tests

```bash
vendor/bin/phpunit --exclude-group redis
```

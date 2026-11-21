# karnoweb/omnichannel

Laravel package for multi-channel publication (9 drivers). Version **13.3.1** (Phase 03: free-form publishing, batch multi-connection publishing, dedicated media, taxonomy mapping, catalog sync foundation).

## Requirements

- PHP ^8.3
- Laravel ^13

## Install

```bash
composer require karnoweb/omnichannel
php artisan vendor:publish --tag=omnichannel-config
php artisan migrate
```

Release notes: [CHANGELOG.md](CHANGELOG.md), [UPGRADING.md](UPGRADING.md), [docs/releases/](docs/releases/README.md). Roadmap: [ROADMAP.md](ROADMAP.md). Contributing: [CONTRIBUTING.md](CONTRIBUTING.md). Phase 5 reports: [docs/phase-05/](docs/phase-05/README.md). Legacy: `docs/hardening/UPGRADE-GUIDE.md`, `docs/phase-02/`, `docs/phase-03/UPGRADING.md`.

## Tests

```bash
vendor/bin/phpunit --exclude-group redis
```
# karnoweb/omnichannel

Laravel package for multi-channel publication (9 drivers). Version **13.3.0** (Phase 03: free-form publishing, batch multi-connection publishing, dedicated media, taxonomy mapping, catalog sync foundation).

## Requirements

- PHP ^8.3
- Laravel ^13

## Install

```bash
composer require karnoweb/omnichannel
php artisan vendor:publish --tag=omnichannel-config
php artisan migrate
```

See `docs/hardening/UPGRADE-GUIDE.md`, `docs/phase-02/`, and `docs/phase-03/UPGRADING.md` for release notes.

## Tests

```bash
vendor/bin/phpunit --exclude-group redis
```
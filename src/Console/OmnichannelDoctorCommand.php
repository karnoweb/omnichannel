<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Karnoweb\Omnichannel\Contracts\MediaBytesResolverContract;
use Karnoweb\Omnichannel\Contracts\ProductRepository;
use Karnoweb\Omnichannel\Enums\CatalogSyncStatus;
use Karnoweb\Omnichannel\Support\OmnichannelTables;

final class OmnichannelDoctorCommand extends Command
{
    protected $signature = 'omnichannel:doctor {--json : Emit machine-readable output}';

    protected $description = 'Check omnichannel configuration and runtime prerequisites.';

    public function handle(): int
    {
        $checks = [
            'php_version' => version_compare(PHP_VERSION, '8.3.0', '>='),
            'curl_extension' => extension_loaded('curl'),
            'fileinfo_extension' => extension_loaded('fileinfo'),
            'pdo_sqlite' => extension_loaded('pdo_sqlite'),
            'tables' => $this->tablesReady(),
            'queue_configured' => is_string(config('omnichannel.queue.name')) && config('omnichannel.queue.name') !== '',
            'cache_lock' => $this->lockable(),
            'drivers' => count((array) config('omnichannel.drivers', [])) === 9,
            'product_repository' => app()->bound(ProductRepository::class),
            'media_resolver' => app()->bound(MediaBytesResolverContract::class),
        ];
        $catalogSync = $this->catalogSyncSummary();

        $failed = array_keys(array_filter($checks, static fn (bool $ok): bool => ! $ok));

        if ((bool) $this->option('json')) {
            $this->line(json_encode(['checks' => $checks, 'catalog_sync' => $catalogSync, 'failed' => $failed], JSON_THROW_ON_ERROR));

            return $failed === [] ? self::SUCCESS : self::FAILURE;
        }

        foreach ($checks as $name => $ok) {
            $this->line(sprintf('[%s] %s', $ok ? 'ok' : 'fail', $name));
        }
        foreach ($catalogSync as $name => $value) {
            $this->line(sprintf('[info] %s=%s', $name, is_scalar($value) ? (string) $value : json_encode($value, JSON_THROW_ON_ERROR)));
        }

        return $failed === [] ? self::SUCCESS : self::FAILURE;
    }

    private function tablesReady(): bool
    {
        foreach (['channels', 'connections', 'publications', 'publication_logs'] as $key) {
            $table = OmnichannelTables::name($key);
            if (! Schema::hasTable($table)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    private function catalogSyncSummary(): array
    {
        $table = OmnichannelTables::name('catalog_sync_states');
        if (! Schema::hasTable($table)) {
            return ['catalog_sync_table' => false];
        }

        $counts = [];
        foreach (CatalogSyncStatus::cases() as $status) {
            $counts[$status->value] = (int) DB::table($table)->where('status', $status->value)->count();
        }

        return [
            'catalog_sync_table' => true,
            'catalog_sync_pending' => $counts[CatalogSyncStatus::Pending->value] ?? 0,
            'catalog_sync_unknown' => $counts[CatalogSyncStatus::Unknown->value] ?? 0,
            'catalog_sync_failed' => $counts[CatalogSyncStatus::Failed->value] ?? 0,
        ];
    }

    private function lockable(): bool
    {
        try {
            return Cache::lock('omnichannel-doctor-lock', 1)->get();
        } catch (\Throwable) {
            return false;
        }
    }
}

<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Karnoweb\Omnichannel\Support\OmnichannelTables;
use Karnoweb\Omnichannel\Tests\TestCase;

final class UpgradeFrom130Test extends TestCase
{
    use RefreshDatabase;

    private const MIGRATION_DIR = __DIR__.'/../../database/migrations_squashed';

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(self::MIGRATION_DIR);
    }

    public function test_history_retention_migrations_apply_on_upgrade(): void
    {
        $migration = require self::MIGRATION_DIR.'/2026_11_20_000002_preserve_publication_history.php';
        $repair = require self::MIGRATION_DIR.'/2026_11_20_000003_repair_publication_history_constraints.php';
        $migration->up();
        $repair->up();
        $this->assertTrue(true);
    }

    public function test_thirteen_three_migrations_reapply_after_rollback(): void
    {
        foreach (array_reverse($this->thirteenThreeMigrationFiles()) as $file) {
            $migration = require $file;
            $migration->down();
        }

        self::assertFalse(Schema::hasTable(OmnichannelTables::name('catalog_sync_states')));

        foreach ($this->thirteenThreeMigrationFiles() as $file) {
            $migration = require $file;
            $migration->up();
        }

        $publications = OmnichannelTables::name('publications');
        self::assertTrue(Schema::hasColumn($publications, 'source_type'));
        self::assertTrue(Schema::hasTable(OmnichannelTables::name('media_assets')));
        self::assertTrue(Schema::hasTable(OmnichannelTables::name('publication_batches')));
        self::assertTrue(Schema::hasTable(OmnichannelTables::name('external_categories')));
        self::assertTrue(Schema::hasTable(OmnichannelTables::name('catalog_sync_states')));
    }

    public function test_publication_sources_migration_is_idempotent_on_upgrade(): void
    {
        $path = self::MIGRATION_DIR.'/2026_11_21_000001_publication_sources.php';
        $migration = require $path;

        $migration->up();
        $migration->up();

        $table = OmnichannelTables::name('publications');
        self::assertTrue(Schema::hasColumn($table, 'source_reference_id'));
    }

    /**
     * @return list<string>
     */
    private function thirteenThreeMigrationFiles(): array
    {
        return [
            self::MIGRATION_DIR.'/2026_11_21_000001_publication_sources.php',
            self::MIGRATION_DIR.'/2026_11_21_000002_publication_media.php',
            self::MIGRATION_DIR.'/2026_11_21_000003_publication_batches.php',
            self::MIGRATION_DIR.'/2026_11_21_000004_marketplace_taxonomy.php',
            self::MIGRATION_DIR.'/2026_11_21_000005_catalog_sync_states.php',
        ];
    }
}
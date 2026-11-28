<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Karnoweb\Omnichannel\Support\OmnichannelTables;
use Karnoweb\Omnichannel\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class UpgradeFrom130Test extends TestCase
{
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

    #[DataProvider('legacyHostKeys')]
    public function test_legacy_rows_and_history_survive_upgrade_and_reapply_with_custom_table_names(
        string $keyType,
        int|string $hostKey,
    ): void {
        if ($this->usesMysqlTestDatabase()) {
            self::markTestSkipped('Legacy upgrade scenario requires SQLite as the default connection.');
        }

        $default = DB::getDefaultConnection();
        config([
            'database.connections.legacy_upgrade' => ['driver' => 'sqlite', 'database' => ':memory:', 'foreign_key_constraints' => true],
            'omnichannel.general.prefix' => 'legacy_',
            'omnichannel.tables.publications' => 'outbound_posts',
            'omnichannel.keys.branch_key_type' => $keyType,
            'omnichannel.keys.user_key_type' => $keyType,
        ]);
        DB::setDefaultConnection('legacy_upgrade');
        Schema::clearResolvedInstance('db.schema');

        try {
            $initial = require self::MIGRATION_DIR.'/2026_11_19_000000_create_omnichannel_schema.php';
            $initial->up();
            $publications = 'legacy_outbound_posts';
            Schema::table($publications, function (Blueprint $table): void {
                $table->dropIndex(['source_type']);
                $table->dropColumn(['source_type', 'source_reference_type', 'source_reference_id', 'source_title']);
                $table->string('product_id')->change();
            });
            $channel = DB::table(OmnichannelTables::name('channels'))->insertGetId(['name' => 'Legacy', 'slug' => 'legacy-demo', 'driver' => 'demo']);
            $connection = DB::table(OmnichannelTables::name('connections'))->insertGetId(['channel_id' => $channel, 'name' => 'Legacy account']);
            $legacy = [
                'branch_id' => $hostKey,
                'created_by' => $hostKey,
                'channel_id' => $channel,
                'channel_connection_id' => $connection,
                'product_id' => 'legacy-product-7',
                'type' => 'product',
                'status' => 'published',
                'content' => json_encode(['media' => [['type' => 'image', 'url' => 'https://assets.test/legacy.jpg']]]),
                'idempotency_key' => 'legacy-product-key',
                'external_id' => 'legacy-external-id',
            ];
            $publicationId = DB::table($publications)->insertGetId($legacy);
            $history = [
                'publication_id' => $publicationId,
                'channel_connection_id' => $connection,
                'operation' => 'publish',
                'status' => 'success',
                'external_id' => $legacy['external_id'],
            ];
            $logs = OmnichannelTables::name('publication_logs');
            $logId = DB::table($logs)->insertGetId($history);

            $migrations = array_map(static fn (string $file) => require $file, $this->thirteenThreeMigrationFiles());
            foreach ($migrations as $migration) {
                $migration->up();
            }
            $this->assertDatabaseHas($publications, ['id' => $publicationId, ...$legacy, 'source_type' => 'product']);
            $this->assertDatabaseHas($logs, ['id' => $logId, ...$history]);

            foreach (array_reverse($migrations) as $migration) {
                $migration->down();
            }
            foreach ($migrations as $migration) {
                $migration->up();
            }
            $this->assertDatabaseHas($publications, ['id' => $publicationId, ...$legacy, 'source_type' => 'product']);
            $this->assertDatabaseHas($logs, ['id' => $logId, ...$history]);
        } finally {
            DB::purge('legacy_upgrade');
            DB::setDefaultConnection($default);
            Schema::clearResolvedInstance('db.schema');
        }
    }

    public static function legacyHostKeys(): iterable
    {
        yield 'integer' => ['int', 42];
        yield 'UUID' => ['uuid', 'de305d54-75b4-431b-adb2-eb6b9e546014'];
        yield 'ULID' => ['ulid', '01ARZ3NDEKTSV4RRFFQ69G5FAV'];
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

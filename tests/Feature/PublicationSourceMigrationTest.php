<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Karnoweb\Omnichannel\Support\OmnichannelTables;
use Karnoweb\Omnichannel\Tests\TestCase;

final class PublicationSourceMigrationTest extends TestCase
{
    use RefreshDatabase;

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations_squashed');
    }

    public function test_fresh_schema_includes_source_columns_and_nullable_product_id(): void
    {
        $table = OmnichannelTables::name('publications');
        self::assertTrue(Schema::hasColumn($table, 'source_type'));
        self::assertTrue(Schema::hasColumn($table, 'source_reference_type'));
        self::assertTrue(Schema::hasColumn($table, 'source_reference_id'));
        self::assertTrue(Schema::hasColumn($table, 'source_title'));
    }
}

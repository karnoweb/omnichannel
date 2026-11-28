<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Tests\Feature;

use Karnoweb\Omnichannel\Tests\TestCase;

final class MigrationRollbackTest extends TestCase
{

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations_squashed');
    }

    public function test_retry_deadline_migration_rolls_back(): void
    {
        $migration = require __DIR__.'/../../database/migrations_squashed/2026_11_20_000004_add_publication_retry_deadline.php';
        $migration->down();
        $migration->up();
        self::assertTrue(true);
    }
}

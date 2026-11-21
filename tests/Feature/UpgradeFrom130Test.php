<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Karnoweb\Omnichannel\Tests\TestCase;

final class UpgradeFrom130Test extends TestCase
{
    use RefreshDatabase;

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations_squashed');
    }

    public function test_history_retention_migrations_apply_on_upgrade(): void
    {
        $migration = require __DIR__.'/../../database/migrations_squashed/2026_11_20_000002_preserve_publication_history.php';
        $repair = require __DIR__.'/../../database/migrations_squashed/2026_11_20_000003_repair_publication_history_constraints.php';
        $migration->up();
        $repair->up();
        $this->assertTrue(true);
    }
}
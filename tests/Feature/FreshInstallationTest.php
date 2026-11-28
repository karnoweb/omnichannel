<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Tests\Feature;

use Illuminate\Support\Facades\Schema;
use Karnoweb\Omnichannel\Support\OmnichannelTables;
use Karnoweb\Omnichannel\Tests\TestCase;

final class FreshInstallationTest extends TestCase
{
    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations_squashed');
    }

    public function test_fresh_install_creates_core_tables(): void
    {
        foreach (['channels', 'connections', 'publications', 'publication_logs'] as $key) {
            self::assertTrue(Schema::hasTable(OmnichannelTables::name($key)));
        }
    }
}

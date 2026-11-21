<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Tests\Architecture;

use Karnoweb\Omnichannel\Tests\Support\SourceScanner;
use PHPUnit\Framework\TestCase;

final class NoHardHostForeignKeyMigrationTest extends TestCase
{
    /** @var list<string> */
    private const FORBIDDEN_FOREIGN_KEY_TABLES = [
        'users',
        'branches',
        'categories',
        'products',
        'shp_products',
        'shp_product_interfaces',
        'orders',
        'com_orders',
    ];

    public function test_migrations_do_not_hard_foreign_key_host_or_cross_domain_tables(): void
    {
        $migrations = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'migrations_squashed';
        $files = SourceScanner::phpFiles($migrations);

        $this->assertNotSame([], $files, 'Expected squashed migration file.');

        foreach ($files as $file) {
            $contents = (string) file_get_contents($file);

            foreach (self::FORBIDDEN_FOREIGN_KEY_TABLES as $table) {
                $quoted = preg_quote($table, '/');

                $this->assertDoesNotMatchRegularExpression(
                    "/->constrained\\(\\s*['\"]{$quoted}['\"]/i",
                    $contents,
                    "Migration {$file} declares hard FK ->constrained('{$table}')."
                );

                $this->assertDoesNotMatchRegularExpression(
                    "/->on\\(\\s*['\"]{$quoted}['\"]/i",
                    $contents,
                    "Migration {$file} declares hard FK ->on('{$table}')."
                );
            }

            $this->assertDoesNotMatchRegularExpression(
                '/^use\s+App\\\\/m',
                $contents,
                "Migration {$file} imports host App\\ namespace."
            );
        }
    }
}

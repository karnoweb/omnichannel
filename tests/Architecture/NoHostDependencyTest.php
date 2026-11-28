<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Tests\Architecture;

use Karnoweb\Omnichannel\Tests\Support\SourceScanner;
use PHPUnit\Framework\TestCase;

final class NoHostDependencyTest extends TestCase
{
    /** @var list<string> */
    private const SCANNED_DIRECTORIES = [
        'src',
        'database/migrations_squashed',
        'database/factories',
        'database/seeders',
        'tests',
    ];

    /** @var list<string> */
    private const FORBIDDEN = [
        'App\\',
        'Karnoweb\\Shop',
        'Karnoweb\\Commerce',
        'Karnoweb\\Accounting',
        'Karnoweb\\Crm',
        'Karnoweb\\Hr',
        'Karnoweb\\Inventory',
        'Karnoweb\\Payment',
    ];

    public function test_package_does_not_import_host_or_sibling_domain_packages(): void
    {
        $root = dirname(__DIR__, 2);

        foreach (self::SCANNED_DIRECTORIES as $directory) {
            $path = $root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $directory);
            $files = SourceScanner::phpFiles($path);
            $this->assertNotSame([], $files, "Expected at least one PHP file under {$directory}.");

            foreach ($files as $file) {
                $contents = (string) file_get_contents($file);
                $names = SourceScanner::importedAndQualifiedNames($contents);

                foreach ($names as $name) {
                    foreach (self::FORBIDDEN as $forbidden) {
                        $this->assertFalse(
                            str_starts_with(strtolower($name), strtolower($forbidden)),
                            "Forbidden namespace [{$forbidden}] referenced as [{$name}] in {$file}"
                        );
                    }
                }
            }
        }
    }
}

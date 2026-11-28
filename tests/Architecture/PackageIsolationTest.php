<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Tests\Architecture;

use Karnoweb\Omnichannel\Tests\Support\SourceScanner;
use Karnoweb\Omnichannel\Tests\TestCase;

final class PackageIsolationTest extends TestCase
{
    public function test_src_does_not_reference_host_app_namespaces(): void
    {
        $violations = [];
        foreach (SourceScanner::phpFiles(__DIR__.'/../../src') as $file) {
            $contents = file_get_contents($file);
            if (! is_string($contents)) {
                continue;
            }
            if (preg_match('/\\\\App\\\\/', $contents) === 1) {
                $violations[] = $file;
            }
        }
        self::assertSame([], $violations);
    }
}

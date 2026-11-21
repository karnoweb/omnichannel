<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Tests;

use Karnoweb\Omnichannel\OmnichannelServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [OmnichannelServiceProvider::class];
    }
}

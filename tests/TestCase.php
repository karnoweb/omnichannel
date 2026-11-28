<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Tests;

use Karnoweb\Omnichannel\OmnichannelServiceProvider;
use Karnoweb\Omnichannel\Tests\Concerns\OmnichannelRefreshDatabase;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    use OmnichannelRefreshDatabase;
    protected function getPackageProviders($app): array
    {
        return [OmnichannelServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $host = getenv('OMNICHANNEL_TEST_MYSQL_HOST');
        if ($host === false || $host === '') {
            return;
        }

        $app['config']->set('database.default', 'mysql');
        $app['config']->set('database.connections.mysql', [
            'driver' => 'mysql',
            'host' => $host,
            'port' => getenv('OMNICHANNEL_TEST_MYSQL_PORT') ?: '3306',
            'database' => getenv('OMNICHANNEL_TEST_MYSQL_DATABASE') ?: 'omnichannel_test',
            'username' => getenv('OMNICHANNEL_TEST_MYSQL_USER') ?: 'root',
            'password' => getenv('OMNICHANNEL_TEST_MYSQL_PASSWORD') ?: '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ]);
    }
}

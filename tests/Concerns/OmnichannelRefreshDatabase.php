<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Tests\Concerns;

use Illuminate\Foundation\Testing\RefreshDatabase;

trait OmnichannelRefreshDatabase
{
    use RefreshDatabase;

    protected function usesMysqlTestDatabase(): bool
    {
        $host = getenv('OMNICHANNEL_TEST_MYSQL_HOST');

        return $host !== false && $host !== '';
    }
}

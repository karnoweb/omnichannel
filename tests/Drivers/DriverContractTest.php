<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Tests\Drivers;

use Karnoweb\Omnichannel\Contracts\ChannelDriver;
use Karnoweb\Omnichannel\Services\ChannelManager;
use Karnoweb\Omnichannel\Tests\TestCase;

final class DriverContractTest extends TestCase
{
    public function test_all_registered_drivers_expose_capabilities(): void
    {
        $manager = app(ChannelManager::class);
        foreach (array_keys(config('omnichannel.drivers', [])) as $key) {
            $driver = $manager->driver($key);
            self::assertInstanceOf(ChannelDriver::class, $driver);
            self::assertSame($key, $driver->key());
            self::assertNotEmpty($driver->capabilities()->supported);
        }
    }
}
<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Tests\Drivers;

use Karnoweb\Omnichannel\Contracts\ChannelDriver;
use Karnoweb\Omnichannel\Contracts\DeactivatableChannelDriver;
use Karnoweb\Omnichannel\Enums\Capability;
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

    public function test_commerce_drivers_expose_product_capability(): void
    {
        foreach (['whatsapp', 'basalam'] as $key) {
            $driver = app(ChannelManager::class)->driver($key);
            self::assertTrue($driver->capabilities()->supports(Capability::Product), $key);
        }
    }

    public function test_deactivate_is_only_advertised_when_driver_implements_contract(): void
    {
        foreach (array_keys(config('omnichannel.drivers', [])) as $key) {
            $driver = app(ChannelManager::class)->driver($key);
            $claimsDeactivate = $driver->capabilities()->supports(Capability::Deactivate);
            $implements = $driver instanceof DeactivatableChannelDriver;

            if ($claimsDeactivate) {
                self::assertTrue($implements, $key.' advertises Deactivate without implementing contract');
            }
        }
    }

    public function test_instagram_and_eitaa_do_not_advertise_delete(): void
    {
        foreach (['instagram', 'eitaa'] as $key) {
            $driver = app(ChannelManager::class)->driver($key);
            self::assertFalse($driver->capabilities()->supports(Capability::Delete), $key);
            self::assertNotEmpty($driver->capabilities()->reasonFor(Capability::Delete));
        }
    }
}
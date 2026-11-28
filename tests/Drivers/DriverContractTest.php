<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Tests\Drivers;

use Illuminate\Support\Facades\Http;
use Karnoweb\Omnichannel\Contracts\ChannelDriver;
use Karnoweb\Omnichannel\Contracts\DeactivatableChannelDriver;
use Karnoweb\Omnichannel\Enums\Capability;
use Karnoweb\Omnichannel\Enums\PublicationType;
use Karnoweb\Omnichannel\Models\ChannelConnection;
use Karnoweb\Omnichannel\Models\Publication;
use Karnoweb\Omnichannel\Services\ChannelManager;
use Karnoweb\Omnichannel\Tests\TestCase;

final class DriverContractTest extends TestCase
{
    public function test_missing_credentials_and_unsupported_operations_do_not_send_requests(): void
    {
        Http::preventStrayRequests();
        Http::fake([]);
        $manager = app(ChannelManager::class);
        $keys = ['demo', 'instagram', 'basalam', 'telegram', 'bale', 'rubika', 'gap', 'eitaa', 'whatsapp'];
        self::assertSame($keys, array_keys(config('omnichannel.drivers')));
        foreach ($keys as $key) {
            $driver = $manager->driver($key);
            $connection = new ChannelConnection;
            self::assertFalse($driver->testConnection($connection)->successful, $key);

            $capabilities = $driver->capabilities();
            $publication = new Publication(['type' => PublicationType::Product, 'content' => []]);
            $publication->setRelation('connection', $connection);
            if (! $capabilities->supportsType(PublicationType::Product)) {
                self::assertTrue($driver->validate($publication)->fails(), $key);
            }
            if (! $capabilities->supports(Capability::Update)) {
                self::assertFalse($driver->update($publication)->successful, $key);
            }
            if (! $capabilities->supports(Capability::Delete)) {
                self::assertFalse($driver->delete($publication)->successful, $key);
            }
        }
        Http::assertNothingSent();
    }

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

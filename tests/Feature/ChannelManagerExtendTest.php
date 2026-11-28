<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Tests\Feature;

use Karnoweb\Omnichannel\Channels\Demo\DemoDriver;
use Karnoweb\Omnichannel\Services\ChannelManager;
use Karnoweb\Omnichannel\Tests\TestCase;

final class ChannelManagerExtendTest extends TestCase
{
    public function test_extend_registers_driver_without_config_entry(): void
    {
        $manager = app(ChannelManager::class);
        $key = 'extended_'.bin2hex(random_bytes(4));
        $this->assertFalse($manager->has($key));
        $manager->extend($key, static fn (): DemoDriver => new DemoDriver);
        $this->assertTrue($manager->has($key));
        $this->assertInstanceOf(DemoDriver::class, $manager->driver($key));
        $this->assertSame('demo', $manager->driver($key)->key());
    }
}

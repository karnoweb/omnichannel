<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Tests\Drivers;

use Karnoweb\Omnichannel\Channels\Instagram\InstagramDriver;
use Karnoweb\Omnichannel\Enums\Capability;
use Karnoweb\Omnichannel\Tests\TestCase;

final class InstagramCapabilitiesTest extends TestCase
{
    public function test_declares_publishing_capabilities_and_documents_delete_gap(): void
    {
        $capabilities = app(InstagramDriver::class)->capabilities();

        self::assertTrue($capabilities->supports(Capability::Post));
        self::assertTrue($capabilities->supports(Capability::Carousel));
        self::assertFalse($capabilities->supports(Capability::Delete));
        self::assertNotEmpty($capabilities->reasonFor(Capability::Delete));
    }

    public function test_internal_rate_limit_config_is_documented_separately_from_meta_quota(): void
    {
        $rate = config('omnichannel.channels.instagram.rate_limit');

        self::assertIsArray($rate);
        self::assertArrayHasKey('attempts', $rate);
        self::assertSame('v26.0', config('omnichannel.channels.instagram.api_version'));
    }
}
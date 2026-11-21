<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Tests\Drivers;

use Karnoweb\Omnichannel\Channels\Bale\BaleDriver;
use Karnoweb\Omnichannel\Channels\Telegram\TelegramDriver;
use Karnoweb\Omnichannel\Tests\TestCase;
use ReflectionMethod;

final class BaleBotApiParseModeTest extends TestCase
{
    public function test_bale_disables_parse_mode_while_telegram_enables_it(): void
    {
        $bale = new ReflectionMethod(BaleDriver::class, 'supportsParseMode');
        $bale->setAccessible(true);
        $telegram = new ReflectionMethod(TelegramDriver::class, 'supportsParseMode');
        $telegram->setAccessible(true);

        self::assertFalse($bale->invoke(app(BaleDriver::class)));
        self::assertTrue($telegram->invoke(app(TelegramDriver::class)));
    }
}
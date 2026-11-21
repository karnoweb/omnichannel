<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Tests\Feature;

use Karnoweb\Omnichannel\Tests\TestCase;
use Karnoweb\Omnichannel\ValueObjects\MediaItem;

final class LegacyMediaItemCompatibilityTest extends TestCase
{
    public function test_legacy_array_shape_round_trips_without_asset_fields(): void
    {
        $payload = ['type' => 'image', 'url' => 'https://cdn.example.test/photo.jpg', 'alt_text' => 'A'];
        $item = MediaItem::fromArray($payload);
        self::assertSame($payload['url'], $item->url);
        self::assertNull($item->assetId);
        self::assertSame($payload, $item->toArray());
    }

    public function test_asset_fields_are_optional_in_constructor(): void
    {
        $item = MediaItem::fromArray([
            'type' => 'image',
            'url' => 'omc-asset:1',
            'asset_id' => 1,
            'checksum' => 'abc',
        ]);
        self::assertSame(1, $item->assetId);
        self::assertSame('abc', $item->checksum);
    }
}
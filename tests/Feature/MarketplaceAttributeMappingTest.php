<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Tests\Feature;

use Karnoweb\Omnichannel\Services\MarketplaceAttributeValidator;
use Karnoweb\Omnichannel\Tests\TestCase;
use RuntimeException;

final class MarketplaceAttributeMappingTest extends TestCase
{
    public function test_attribute_rules_map_internal_fields(): void
    {
        $mapped = app(MarketplaceAttributeValidator::class)->mapAttributes([
            'color' => 'attributes.color',
            'weight' => ['source' => 'attributes.weight', 'default' => 100],
        ], [
            'attributes' => ['color' => 'red', 'weight' => 250],
        ]);
        self::assertSame(['color' => 'red', 'weight' => 250], $mapped);
    }

    public function test_required_attribute_missing_throws(): void
    {
        $this->expectException(RuntimeException::class);
        app(MarketplaceAttributeValidator::class)->mapAttributes([
            'size' => ['source' => 'attributes.size', 'required' => true],
        ], ['attributes' => []]);
    }
}
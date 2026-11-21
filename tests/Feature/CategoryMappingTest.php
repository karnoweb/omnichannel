<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Karnoweb\Omnichannel\Models\CategoryMapping;
use Karnoweb\Omnichannel\Models\Channel;
use Karnoweb\Omnichannel\Models\ChannelConnection;
use Karnoweb\Omnichannel\Models\ExternalCategory;
use Karnoweb\Omnichannel\Services\CategoryMappingResolver;
use Karnoweb\Omnichannel\Tests\TestCase;

final class CategoryMappingTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('f', 32)));
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations_squashed');
    }

    public function test_mapping_resolves_external_category_id_for_connection(): void
    {
        $channel = Channel::query()->create(['name' => 'Basalam', 'slug' => 'basalam', 'driver' => 'basalam']);
        $connection = ChannelConnection::query()->create(['branch_id' => 3, 'channel_id' => $channel->id, 'name' => 'Shop', 'credentials' => [], 'status' => 'active']);
        $external = ExternalCategory::query()->create([
            'channel_id' => $channel->id,
            'scope_key' => 'global',
            'external_id' => '501',
            'name' => 'Electronics',
            'is_leaf' => true,
            'is_active' => true,
        ]);
        CategoryMapping::query()->create([
            'branch_id' => 3,
            'channel_connection_id' => $connection->id,
            'internal_category_id' => 'cat-electronics',
            'external_category_id' => $external->id,
            'is_active' => true,
        ]);
        $resolved = app(CategoryMappingResolver::class)->resolveExternalCategoryId('cat-electronics', $connection, 3);
        self::assertSame(501, $resolved);
    }
}
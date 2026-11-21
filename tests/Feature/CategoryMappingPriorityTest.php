<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Karnoweb\Omnichannel\Channels\Basalam\BasalamCategoryMapper;
use Karnoweb\Omnichannel\Models\CategoryMapping;
use Karnoweb\Omnichannel\Models\Channel;
use Karnoweb\Omnichannel\Models\ChannelConnection;
use Karnoweb\Omnichannel\Models\ExternalCategory;
use Karnoweb\Omnichannel\Tests\TestCase;

final class CategoryMappingPriorityTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('g', 32)));
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations_squashed');
    }

    public function test_database_mapping_takes_priority_over_config_defaults(): void
    {
        config()->set('omnichannel.channels.basalam.category_map', ['Handicrafts' => 100]);
        $channel = Channel::query()->create(['name' => 'Basalam', 'slug' => 'basalam-priority', 'driver' => 'basalam']);
        $connection = ChannelConnection::query()->create(['branch_id' => 6, 'channel_id' => $channel->id, 'name' => 'Shop', 'credentials' => [], 'status' => 'active']);
        $external = ExternalCategory::query()->create([
            'channel_id' => $channel->id,
            'scope_key' => 'global',
            'external_id' => '777',
            'name' => 'Mapped',
            'is_leaf' => true,
            'is_active' => true,
        ]);
        CategoryMapping::query()->create([
            'branch_id' => 6,
            'channel_connection_id' => $connection->id,
            'internal_category_id' => 'Handicrafts',
            'external_category_id' => $external->id,
            'is_active' => true,
        ]);
        $mapper = app(BasalamCategoryMapper::class);
        self::assertSame(777, $mapper->resolve('Handicrafts', $connection));
    }
}
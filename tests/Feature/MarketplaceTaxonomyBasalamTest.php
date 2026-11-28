<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Karnoweb\Omnichannel\Models\Channel;
use Karnoweb\Omnichannel\Models\ChannelConnection;
use Karnoweb\Omnichannel\Models\ExternalCategory;
use Karnoweb\Omnichannel\Services\MarketplaceTaxonomyService;
use Karnoweb\Omnichannel\Tests\TestCase;

final class MarketplaceTaxonomyBasalamTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('g', 32)));
        $app['config']->set('omnichannel.channels.basalam.base_url', 'https://basalam.test');
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations_squashed');
    }

    public function test_taxonomy_sync_persists_categories(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://basalam.test/v1/categories' => Http::response([
                'data' => [
                    ['id' => 10, 'title' => 'Books', 'is_leaf' => true],
                    ['id' => 11, 'title' => 'Kids', 'parent_id' => 10, 'is_leaf' => true],
                ],
            ]),
        ]);

        $channel = Channel::query()->create(['name' => 'Basalam', 'slug' => 'basalam-tax', 'driver' => 'basalam']);
        $connection = ChannelConnection::query()->create([
            'branch_id' => 1,
            'channel_id' => $channel->id,
            'name' => 'Shop',
            'credentials' => ['access_token' => 'token'],
            'status' => 'active',
        ]);

        $count = app(MarketplaceTaxonomyService::class)->syncConnection($connection);

        self::assertSame(2, $count);
        self::assertSame(2, ExternalCategory::query()->where('channel_id', $channel->id)->count());
    }

    public function test_taxonomy_dry_run_does_not_persist(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://basalam.test/v1/categories' => Http::response(['data' => [['id' => 99, 'title' => 'X']]]),
        ]);

        $channel = Channel::query()->create(['name' => 'Basalam', 'slug' => 'basalam-dry', 'driver' => 'basalam']);
        $connection = ChannelConnection::query()->create([
            'branch_id' => 1,
            'channel_id' => $channel->id,
            'name' => 'Shop',
            'credentials' => ['access_token' => 'token'],
            'status' => 'active',
        ]);

        $count = app(MarketplaceTaxonomyService::class)->syncConnection($connection, dryRun: true);

        self::assertSame(1, $count);
        self::assertSame(0, ExternalCategory::query()->count());
    }
}

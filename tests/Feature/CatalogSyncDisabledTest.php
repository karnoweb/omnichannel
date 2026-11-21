<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Karnoweb\Omnichannel\Enums\CatalogSyncStatus;
use Karnoweb\Omnichannel\Enums\PublicationSourceType;
use Karnoweb\Omnichannel\Enums\PublicationStatus;
use Karnoweb\Omnichannel\Enums\PublicationType;
use Karnoweb\Omnichannel\Models\CatalogSyncState;
use Karnoweb\Omnichannel\Models\Channel;
use Karnoweb\Omnichannel\Models\ChannelConnection;
use Karnoweb\Omnichannel\Models\Publication;
use Karnoweb\Omnichannel\Services\CatalogSyncDispatcher;
use Karnoweb\Omnichannel\Tests\TestCase;

final class CatalogSyncDisabledTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('d', 32)));
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations_squashed');
    }

    public function test_dispatcher_marks_disabled_when_catalog_sync_off(): void
    {
        config()->set('omnichannel.catalog_sync.enabled', false);
        $channel = Channel::query()->create(['name' => 'Basalam', 'slug' => 'basalam', 'driver' => 'basalam']);
        $connection = ChannelConnection::query()->create(['branch_id' => 1, 'channel_id' => $channel->id, 'name' => 'S', 'credentials' => [], 'status' => 'active', 'metadata' => ['catalog_sync_enabled' => true]]);
        $publication = Publication::query()->create([
            'branch_id' => 1,
            'source_type' => PublicationSourceType::Product,
            'product_id' => 'x',
            'channel_id' => $channel->id,
            'channel_connection_id' => $connection->id,
            'type' => PublicationType::Product,
            'status' => PublicationStatus::Published,
            'content' => [],
            'external_id' => 'ext-1',
            'idempotency_key' => 'k1',
        ]);
        $state = CatalogSyncState::query()->create([
            'branch_id' => 1,
            'publication_id' => $publication->id,
            'listing_identity' => 'abc',
            'status' => CatalogSyncStatus::Pending,
        ]);
        app(CatalogSyncDispatcher::class)->dispatchPending($state->refresh());
        self::assertSame(CatalogSyncStatus::Disabled, $state->refresh()->status);
    }
}
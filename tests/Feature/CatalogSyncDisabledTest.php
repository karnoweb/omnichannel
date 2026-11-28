<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Tests\Feature;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Karnoweb\Omnichannel\Channels\Basalam\BasalamDriver;
use Karnoweb\Omnichannel\Enums\CatalogSyncStatus;
use Karnoweb\Omnichannel\Enums\ConnectionStatus;
use Karnoweb\Omnichannel\Enums\PublicationSourceType;
use Karnoweb\Omnichannel\Enums\PublicationStatus;
use Karnoweb\Omnichannel\Enums\PublicationType;
use Karnoweb\Omnichannel\Jobs\DispatchPendingCatalogSyncsJob;
use Karnoweb\Omnichannel\Models\CatalogSyncState;
use Karnoweb\Omnichannel\Models\Channel;
use Karnoweb\Omnichannel\Models\ChannelConnection;
use Karnoweb\Omnichannel\Models\Publication;
use Karnoweb\Omnichannel\Services\CatalogSyncDispatcher;
use Karnoweb\Omnichannel\Services\CatalogSyncPolicy;
use Karnoweb\Omnichannel\Services\ChannelManager;
use Karnoweb\Omnichannel\Tests\TestCase;
use Karnoweb\Omnichannel\ValueObjects\CatalogListingIdentity;

final class CatalogSyncDisabledTest extends TestCase
{
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
        Queue::fake();
        Http::preventStrayRequests();
        config()->set('omnichannel.drivers.basalam', BasalamDriver::class);
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
        Queue::assertNothingPushed();
        Http::assertNothingSent();
    }

    public function test_enabled_dispatch_never_marks_sync_success_without_provider_confirmation(): void
    {
        config()->set('omnichannel.catalog_sync.enabled', true);
        config()->set('omnichannel.channels.basalam.base_url', 'https://basalam.test');
        Queue::fake();
        Http::preventStrayRequests();
        config()->set('omnichannel.drivers.basalam', BasalamDriver::class);
        $channel = Channel::query()->create(['name' => 'Basalam', 'slug' => 'basalam-pending', 'driver' => 'basalam']);
        $connection = ChannelConnection::query()->create([
            'branch_id' => 1, 'channel_id' => $channel->id, 'name' => 'Shop', 'status' => ConnectionStatus::Active,
            'credentials' => ['access_token' => 'sample-token'], 'metadata' => ['catalog_sync_enabled' => true],
        ]);
        $publication = Publication::query()->create([
            'branch_id' => 1, 'source_type' => PublicationSourceType::Product, 'product_id' => 'sku',
            'channel_id' => $channel->id, 'channel_connection_id' => $connection->id,
            'type' => PublicationType::Product, 'status' => PublicationStatus::Published,
            'content' => [], 'external_id' => 'listing-1', 'idempotency_key' => 'pending-sync',
        ]);
        $identity = new CatalogListingIdentity($connection->id, 'sku', 'listing-1');
        $state = CatalogSyncState::query()->create([
            'branch_id' => 1, 'publication_id' => $publication->id,
            'listing_identity' => $identity->key(), 'status' => CatalogSyncStatus::Pending,
            'metadata' => [],
        ]);

        $job = new DispatchPendingCatalogSyncsJob($state->id);
        $job->handle(app(ChannelManager::class), app(CatalogSyncPolicy::class));

        self::assertSame(CatalogSyncStatus::Failed, $state->fresh()->status);
        self::assertNull($state->fresh()->last_synced_at);
        self::assertStringContainsString('snapshot', strtolower($state->fresh()->last_error ?? ''));
        Http::assertNothingSent();

        $state->forceFill(['metadata' => ['desired_snapshot' => ['price' => 1000, 'stock' => 5]]])->save();

        $publication->forceFill([
            'content' => [
                'title' => 'Title',
                'caption' => 'Caption',
                'media' => [['type' => 'image', 'url' => 'https://assets.test/p.jpg']],
                'extra' => ['price' => 1000, 'stock' => 5],
            ],
        ])->save();
        Http::fake(['https://basalam.test/v1/products/listing-1' => Http::response(['id' => 'listing-1'])]);
        $state->forceFill(['status' => CatalogSyncStatus::Pending, 'last_error' => null])->save();

        $job = new DispatchPendingCatalogSyncsJob($state->id);
        $job->handle(app(ChannelManager::class), app(CatalogSyncPolicy::class));

        self::assertSame(CatalogSyncStatus::Synced, $state->fresh()->status);
        self::assertNotNull($state->fresh()->last_synced_at);
    }
}

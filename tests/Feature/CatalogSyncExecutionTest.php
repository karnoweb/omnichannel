<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
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
use Karnoweb\Omnichannel\Services\ChannelManager;
use Karnoweb\Omnichannel\Tests\TestCase;
use Karnoweb\Omnichannel\ValueObjects\CatalogListingIdentity;

final class CatalogSyncExecutionTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('e', 32)));
        $app['config']->set('omnichannel.channels.basalam.base_url', 'https://basalam.test');
        $app['config']->set('omnichannel.channels.whatsapp.graph_url', 'https://graph.test');
        $app['config']->set('omnichannel.channels.whatsapp.api_version', 'v1.0');
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations_squashed');
    }

    public function test_basalam_price_stock_sync_marks_synced_after_provider_success(): void
    {
        config()->set('omnichannel.catalog_sync.enabled', true);
        config()->set('omnichannel.drivers.basalam', BasalamDriver::class);
        Http::preventStrayRequests();
        Http::fake([
            'https://basalam.test/v1/products/55' => Http::response(['id' => 55]),
        ]);

        [$state, $publication] = $this->basalamPublication('listing-basalam');

        $job = new DispatchPendingCatalogSyncsJob($state->id);
        $job->handle(app(ChannelManager::class));

        $state->refresh();
        self::assertSame(CatalogSyncStatus::Synced, $state->status);
        self::assertNotNull($state->last_synced_at);
        self::assertNull($state->last_error);
        self::assertSame(2200, (int) $publication->fresh()->content()->extra('price'));
        self::assertSame(9, (int) $publication->fresh()->content()->extra('stock'));

        Http::assertSent(function ($request): bool {
            if (! str_contains($request->url(), '/v1/products/55')) {
                return false;
            }

            return $request->method() === 'PATCH'
                && ($request->data()['primary_price'] ?? null) === 2200
                && ($request->data()['stock'] ?? null) === 9;
        });
    }

    public function test_provider_failure_does_not_mark_synced(): void
    {
        config()->set('omnichannel.catalog_sync.enabled', true);
        config()->set('omnichannel.drivers.basalam', BasalamDriver::class);
        Http::preventStrayRequests();
        Http::fake([
            'https://basalam.test/v1/products/55' => Http::response(['message' => 'invalid'], 422),
        ]);

        [$state] = $this->basalamPublication('listing-fail');

        $job = new DispatchPendingCatalogSyncsJob($state->id);
        $job->handle(app(ChannelManager::class));

        $state->refresh();
        self::assertSame(CatalogSyncStatus::Failed, $state->status);
        self::assertNull($state->last_synced_at);
        self::assertNotNull($state->last_error);
    }

    public function test_whatsapp_batch_acceptance_stays_unknown_not_synced(): void
    {
        config()->set('omnichannel.catalog_sync.enabled', true);
        Http::preventStrayRequests();
        Http::fake([
            'https://graph.test/v1.0/cat-1/items_batch' => Http::response(['handles' => ['h-1']]),
        ]);

        $channel = Channel::query()->create(['name' => 'WA', 'slug' => 'wa-sync', 'driver' => 'whatsapp']);
        $connection = ChannelConnection::query()->create([
            'branch_id' => 1,
            'channel_id' => $channel->id,
            'name' => 'Shop',
            'status' => ConnectionStatus::Active,
            'credentials' => ['access_token' => 'token', 'catalog_id' => 'cat-1'],
            'metadata' => ['catalog_sync_enabled' => true],
        ]);
        $publication = Publication::query()->create([
            'branch_id' => 1,
            'source_type' => PublicationSourceType::Product,
            'product_id' => 'sku-wa',
            'channel_id' => $channel->id,
            'channel_connection_id' => $connection->id,
            'type' => PublicationType::Product,
            'status' => PublicationStatus::Published,
            'content' => [
                'title' => 'Item',
                'caption' => 'Desc',
                'media' => [['type' => 'image', 'url' => 'https://cdn.test/x.jpg']],
                'extra' => [
                    'price' => 100,
                    'currency' => 'IRR',
                    'whatsapp' => ['brand' => 'B', 'link' => 'https://shop.test/p'],
                ],
            ],
            'external_id' => 'ret-1',
            'idempotency_key' => 'wa-sync-1',
        ]);
        $identity = new CatalogListingIdentity($connection->id, 'sku-wa', 'ret-1');
        $state = CatalogSyncState::query()->create([
            'branch_id' => 1,
            'publication_id' => $publication->id,
            'listing_identity' => $identity->key(),
            'status' => CatalogSyncStatus::Pending,
            'metadata' => ['desired_snapshot' => ['price' => 150, 'stock' => 2]],
        ]);

        $job = new DispatchPendingCatalogSyncsJob($state->id);
        $job->handle(app(ChannelManager::class));

        $state->refresh();
        self::assertSame(CatalogSyncStatus::Unknown, $state->status);
        self::assertNull($state->last_synced_at);
    }

    /**
     * @return array{0: CatalogSyncState, 1: Publication}
     */
    private function basalamPublication(string $idempotency): array
    {
        $channel = Channel::query()->create(['name' => 'Basalam', 'slug' => 'basalam-sync', 'driver' => 'basalam']);
        $connection = ChannelConnection::query()->create([
            'branch_id' => 1,
            'channel_id' => $channel->id,
            'name' => 'Shop',
            'status' => ConnectionStatus::Active,
            'credentials' => ['access_token' => 'sample-token'],
            'metadata' => ['catalog_sync_enabled' => true, 'vendor_id' => 42],
        ]);
        $publication = Publication::query()->create([
            'branch_id' => 1,
            'source_type' => PublicationSourceType::Product,
            'product_id' => 'sku-b',
            'channel_id' => $channel->id,
            'channel_connection_id' => $connection->id,
            'type' => PublicationType::Product,
            'status' => PublicationStatus::Published,
            'content' => [
                'title' => 'Example',
                'caption' => 'Description line',
                'media' => [['type' => 'image', 'url' => 'https://assets.test/p.jpg']],
                'extra' => ['price' => 1000, 'stock' => 1],
            ],
            'external_id' => '55',
            'idempotency_key' => $idempotency,
        ]);
        $identity = new CatalogListingIdentity($connection->id, 'sku-b', '55');
        $state = CatalogSyncState::query()->create([
            'branch_id' => 1,
            'publication_id' => $publication->id,
            'listing_identity' => $identity->key(),
            'status' => CatalogSyncStatus::Pending,
            'metadata' => ['desired_snapshot' => ['price' => 2200, 'stock' => 9]],
        ]);

        return [$state, $publication];
    }
}

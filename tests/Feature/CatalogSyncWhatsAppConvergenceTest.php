<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Tests\Feature;

use Illuminate\Support\Facades\Http;
use Karnoweb\Omnichannel\Enums\CatalogSyncStatus;
use Karnoweb\Omnichannel\Enums\ConnectionStatus;
use Karnoweb\Omnichannel\Enums\PublicationSourceType;
use Karnoweb\Omnichannel\Enums\PublicationStatus;
use Karnoweb\Omnichannel\Enums\PublicationType;
use Karnoweb\Omnichannel\Models\CatalogSyncState;
use Karnoweb\Omnichannel\Models\Channel;
use Karnoweb\Omnichannel\Models\ChannelConnection;
use Karnoweb\Omnichannel\Models\Publication;
use Karnoweb\Omnichannel\Results\ReconciliationResult;
use Karnoweb\Omnichannel\Services\CatalogSyncFingerprint;
use Karnoweb\Omnichannel\Services\WhatsAppCatalogReconciliationService;
use Karnoweb\Omnichannel\Tests\TestCase;
use Karnoweb\Omnichannel\ValueObjects\CatalogListingIdentity;

final class CatalogSyncWhatsAppConvergenceTest extends TestCase
{

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('h', 32)));
        $app['config']->set('omnichannel.channels.whatsapp.graph_url', 'https://graph.test');
        $app['config']->set('omnichannel.channels.whatsapp.api_version', 'v1.0');
        $app['config']->set('omnichannel.whatsapp_catalog.auto_reconcile', true);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations_squashed');
    }

    public function test_finalize_batch_moves_unknown_catalog_sync_to_synced(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://graph.test/v1.0/cat-1/check_batch_request_status*' => Http::response([
                'data' => [[
                    'handle' => 'h-1',
                    'status' => 'finished',
                    'errors_total_count' => 0,
                ]],
            ]),
        ]);

        $channel = Channel::query()->create(['name' => 'WA', 'slug' => 'wa-conv', 'driver' => 'whatsapp']);
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
            'idempotency_key' => 'wa-conv',
        ]);
        $snapshot = ['price' => 150, 'stock' => 2];
        $fingerprint = CatalogSyncFingerprint::fromSnapshot($snapshot);
        $identity = new CatalogListingIdentity($connection->id, 'sku-wa', 'ret-1');
        $state = CatalogSyncState::query()->create([
            'branch_id' => 1,
            'publication_id' => $publication->id,
            'listing_identity' => $identity->key(),
            'status' => CatalogSyncStatus::Unknown,
            'metadata' => [
                'desired_snapshot' => $fingerprint['canonical'],
                'desired_revision' => $fingerprint['revision'],
            ],
        ]);

        $publication->rememberPersistentArtifact('update', 'pending_batch', [
            'handles' => ['h-1'],
            'status' => 'pending',
        ]);

        $result = app(WhatsAppCatalogReconciliationService::class)->finalizeWhenReady($publication);

        self::assertSame(ReconciliationResult::ConfirmedSuccess, $result->outcome);
        $state->refresh();
        self::assertSame(CatalogSyncStatus::Synced, $state->status);
        self::assertNotNull($state->last_synced_at);
        self::assertSame($fingerprint['revision'], data_get($state->metadata, 'applied_revision'));
    }
}

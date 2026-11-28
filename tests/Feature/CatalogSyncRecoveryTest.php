<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
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
use Karnoweb\Omnichannel\Services\CatalogSyncRecoveryService;
use Karnoweb\Omnichannel\Tests\TestCase;
use Karnoweb\Omnichannel\ValueObjects\CatalogListingIdentity;

final class CatalogSyncRecoveryTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('i', 32)));
        $app['config']->set('omnichannel.catalog_sync.debounce_seconds', 0);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations_squashed');
    }

    public function test_recover_stale_dispatches_eligible_states(): void
    {
        config()->set('omnichannel.catalog_sync.enabled', true);
        Queue::fake();

        [$state] = $this->eligiblePublication();

        $state->forceFill([
            'status' => CatalogSyncStatus::Failed,
            'last_dispatched_at' => now()->subHours(3),
            'attempts' => 1,
        ])->save();

        $count = app(CatalogSyncRecoveryService::class)->recoverStale();

        self::assertSame(1, $count);
        Queue::assertPushed(DispatchPendingCatalogSyncsJob::class);
    }

    public function test_recover_returns_zero_when_catalog_sync_disabled(): void
    {
        config()->set('omnichannel.catalog_sync.enabled', false);
        Queue::fake();

        [$state] = $this->eligiblePublication();
        $state->forceFill(['status' => CatalogSyncStatus::Failed, 'last_dispatched_at' => now()->subHours(3)])->save();

        self::assertSame(0, app(CatalogSyncRecoveryService::class)->recoverStale());
        Queue::assertNothingPushed();
    }

    /**
     * @return array{0: CatalogSyncState}
     */
    private function eligiblePublication(): array
    {
        $channel = Channel::query()->create(['name' => 'Basalam', 'slug' => 'basalam-rec', 'driver' => 'basalam']);
        $connection = ChannelConnection::query()->create([
            'branch_id' => 2,
            'channel_id' => $channel->id,
            'name' => 'Shop',
            'status' => ConnectionStatus::Active,
            'credentials' => ['access_token' => 'token'],
            'metadata' => ['catalog_sync_enabled' => true],
        ]);
        $publication = Publication::query()->create([
            'branch_id' => 2,
            'source_type' => PublicationSourceType::Product,
            'product_id' => 'sku-r',
            'channel_id' => $channel->id,
            'channel_connection_id' => $connection->id,
            'type' => PublicationType::Product,
            'status' => PublicationStatus::Published,
            'content' => ['title' => 'T', 'caption' => 'C', 'media' => [['type' => 'image', 'url' => 'https://x.test/a.jpg']], 'extra' => ['price' => 1]],
            'external_id' => 'ex',
            'idempotency_key' => 'rec',
        ]);
        $identity = new CatalogListingIdentity($connection->id, 'sku-r', 'ex');
        $state = CatalogSyncState::query()->create([
            'branch_id' => 2,
            'publication_id' => $publication->id,
            'listing_identity' => $identity->key(),
            'status' => CatalogSyncStatus::Pending,
            'metadata' => ['desired_snapshot' => ['price' => 1, 'stock' => 1]],
        ]);

        return [$state];
    }
}

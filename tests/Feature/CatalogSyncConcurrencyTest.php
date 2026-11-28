<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Tests\Feature;

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
use Karnoweb\Omnichannel\Services\CatalogSyncFingerprint;
use Karnoweb\Omnichannel\Services\CatalogSyncPolicy;
use Karnoweb\Omnichannel\Services\ChannelManager;
use Karnoweb\Omnichannel\Tests\TestCase;
use Karnoweb\Omnichannel\ValueObjects\CatalogListingIdentity;

final class CatalogSyncConcurrencyTest extends TestCase
{

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('f', 32)));
        $app['config']->set('omnichannel.channels.basalam.base_url', 'https://basalam.test');
        $app['config']->set('omnichannel.catalog_sync.debounce_seconds', 0);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations_squashed');
    }

    public function test_stale_claimed_revision_skips_provider_call(): void
    {
        config()->set('omnichannel.catalog_sync.enabled', true);
        config()->set('omnichannel.drivers.basalam', BasalamDriver::class);
        Http::preventStrayRequests();

        [$state] = $this->basalamPublication('stale-claim');
        $staleRevision = data_get($state->metadata, 'desired_revision');
        self::assertIsString($staleRevision);

        $state->forceFill([
            'metadata' => [
                'desired_snapshot' => ['price' => 9999, 'stock' => 1],
                'desired_revision' => 'new-revision',
            ],
            'status' => CatalogSyncStatus::Pending,
        ])->save();

        $job = new DispatchPendingCatalogSyncsJob($state->id, $staleRevision);
        $job->handle(app(ChannelManager::class), app(CatalogSyncPolicy::class));

        Http::assertNothingSent();
        self::assertSame(CatalogSyncStatus::Pending, $state->fresh()->status);
    }

    public function test_changed_listing_identity_fails_without_provider_call(): void
    {
        config()->set('omnichannel.catalog_sync.enabled', true);
        config()->set('omnichannel.drivers.basalam', BasalamDriver::class);
        Http::preventStrayRequests();

        [$state, $publication] = $this->basalamPublication('listing-change');
        $publication->forceFill(['external_id' => 'new-external'])->save();

        $job = new DispatchPendingCatalogSyncsJob($state->id);
        $job->handle(app(ChannelManager::class), app(CatalogSyncPolicy::class));

        Http::assertNothingSent();
        self::assertSame(CatalogSyncStatus::Failed, $state->fresh()->status);
        self::assertStringContainsString('listing identity', strtolower($state->fresh()->last_error ?? ''));
    }

    /**
     * @return array{0: CatalogSyncState, 1: Publication}
     */
    private function basalamPublication(string $idempotency): array
    {
        $channel = Channel::query()->create(['name' => 'Basalam', 'slug' => 'basalam-'.$idempotency, 'driver' => 'basalam']);
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
            'product_id' => 'sku-'.$idempotency,
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
        $identity = new CatalogListingIdentity($connection->id, 'sku-'.$idempotency, '55');
        $snapshot = ['price' => 2200, 'stock' => 9];
        $fingerprint = CatalogSyncFingerprint::fromSnapshot($snapshot);
        $state = CatalogSyncState::query()->create([
            'branch_id' => 1,
            'publication_id' => $publication->id,
            'listing_identity' => $identity->key(),
            'status' => CatalogSyncStatus::Pending,
            'metadata' => [
                'desired_snapshot' => $fingerprint['canonical'],
                'desired_revision' => $fingerprint['revision'],
            ],
        ]);

        return [$state, $publication];
    }
}

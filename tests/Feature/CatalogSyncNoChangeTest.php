<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Tests\Feature;

use Karnoweb\Omnichannel\Enums\CatalogSyncStatus;
use Karnoweb\Omnichannel\Enums\PublicationSourceType;
use Karnoweb\Omnichannel\Enums\PublicationStatus;
use Karnoweb\Omnichannel\Enums\PublicationType;
use Karnoweb\Omnichannel\Models\Channel;
use Karnoweb\Omnichannel\Models\ChannelConnection;
use Karnoweb\Omnichannel\Models\Publication;
use Karnoweb\Omnichannel\Services\CatalogSyncService;
use Karnoweb\Omnichannel\Tests\TestCase;

final class CatalogSyncNoChangeTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('e', 32)));
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations_squashed');
    }

    public function test_identical_snapshot_does_not_requeue_sync(): void
    {
        config()->set('omnichannel.catalog_sync.enabled', true);
        $channel = Channel::query()->create(['name' => 'Basalam', 'slug' => 'basalam-nc', 'driver' => 'basalam']);
        $connection = ChannelConnection::query()->create([
            'branch_id' => 4,
            'channel_id' => $channel->id,
            'name' => 'S',
            'credentials' => [],
            'status' => 'active',
            'metadata' => ['catalog_sync_enabled' => true],
        ]);
        $publication = Publication::query()->create([
            'branch_id' => 4,
            'source_type' => PublicationSourceType::Product,
            'product_id' => 'sku',
            'channel_id' => $channel->id,
            'channel_connection_id' => $connection->id,
            'type' => PublicationType::Product,
            'status' => PublicationStatus::Published,
            'content' => [],
            'external_id' => 'ext',
            'idempotency_key' => 'idem',
        ]);
        $service = app(CatalogSyncService::class);
        $first = $service->recordProductChange($publication, ['price' => 1000, 'stock' => 5]);
        self::assertNotNull($first);
        $first->status = CatalogSyncStatus::Synced;
        $first->save();
        $second = $service->recordProductChange($publication, ['price' => 1000, 'stock' => 5]);
        self::assertSame(CatalogSyncStatus::Synced, $second?->status);
    }
}

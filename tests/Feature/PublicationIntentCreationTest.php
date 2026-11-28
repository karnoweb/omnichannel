<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Tests\Feature;

use DateTimeImmutable;
use Karnoweb\Omnichannel\Contracts\BranchContextResolverContract;
use Karnoweb\Omnichannel\Contracts\ProductRepository;
use Karnoweb\Omnichannel\Enums\PublicationStatus;
use Karnoweb\Omnichannel\Enums\PublicationType;
use Karnoweb\Omnichannel\Models\Channel;
use Karnoweb\Omnichannel\Models\ChannelConnection;
use Karnoweb\Omnichannel\Models\Publication;
use Karnoweb\Omnichannel\Services\ArrayProductRepository;
use Karnoweb\Omnichannel\Services\IdempotencyKeyGenerator;
use Karnoweb\Omnichannel\Services\PublishingService;
use Karnoweb\Omnichannel\Support\OmnichannelContext;
use Karnoweb\Omnichannel\Tests\TestCase;
use Karnoweb\Omnichannel\ValueObjects\PublicationContent;

final class PublicationIntentCreationTest extends TestCase
{

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('b', 32)));
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations_squashed');
    }

    public function test_duplicate_requests_share_a_record_but_new_intents_and_schedules_do_not(): void
    {
        $this->setBranch(12);
        $context = app(OmnichannelContext::class);
        $this->app->instance(ProductRepository::class, new ArrayProductRepository([
            ['id' => 'p1', 'branch_id' => 12, 'name' => 'Product'],
        ], $context));
        $connection = $this->connection();
        $content = new PublicationContent(caption: 'hello');
        $service = app(PublishingService::class);
        $scheduledAt = new DateTimeImmutable('2026-12-01T12:00:00+03:30');

        $first = $service->create('p1', $connection, PublicationType::Post, $content, $scheduledAt);
        $duplicate = $service->create('p1', $connection, PublicationType::Post, $content, $scheduledAt);
        $later = $service->create('p1', $connection, PublicationType::Post, $content, new DateTimeImmutable('2026-12-02T08:30:00+00:00'));
        $republish = $service->create('p1', $connection, PublicationType::Post, $content, $scheduledAt, allowDuplicate: true, intentId: 'republish-1');
        $republishRetry = $service->create('p1', $connection, PublicationType::Post, $content, $scheduledAt, allowDuplicate: true, intentId: 'republish-1');
        $secondRepublish = $service->create('p1', $connection, PublicationType::Post, $content, $scheduledAt, allowDuplicate: true, intentId: 'republish-2');

        self::assertSame($first->id, $duplicate->id);
        self::assertNotSame($first->id, $later->id);
        self::assertSame($republish->id, $republishRetry->id);
        self::assertNotSame($republish->id, $secondRepublish->id);
    }

    public function test_legacy_key_reuses_the_existing_publication(): void
    {
        $this->setBranch(12);
        $context = app(OmnichannelContext::class);
        $this->app->instance(ProductRepository::class, new ArrayProductRepository([
            ['id' => 'p1', 'branch_id' => 12, 'name' => 'Product'],
        ], $context));
        $connection = $this->connection();
        $content = new PublicationContent(caption: 'hello');
        $legacyKey = app(IdempotencyKeyGenerator::class)->forLegacyPublication($connection, 'p1', PublicationType::Post, $content);
        $existing = Publication::factory()->create([
            'branch_id' => 12,
            'channel_id' => $connection->channel_id,
            'channel_connection_id' => $connection->id,
            'product_id' => 'p1',
            'type' => PublicationType::Post,
            'status' => PublicationStatus::Published,
            'content' => $content->toArray(),
            'idempotency_key' => $legacyKey,
            'external_id' => 'already-posted',
        ]);

        $found = app(PublishingService::class)->create('p1', $connection, PublicationType::Post, $content);

        self::assertSame($existing->id, $found->id);
        self::assertSame(1, Publication::query()->where('idempotency_key', $legacyKey)->count());
        $scheduled = app(PublishingService::class)->create('p1', $connection, PublicationType::Post, $content, new DateTimeImmutable('2026-12-01'));
        self::assertNotSame($existing->id, $scheduled->id);
    }

    private function connection(): ChannelConnection
    {
        $channel = Channel::query()->create(['name' => 'Demo', 'slug' => 'demo', 'driver' => 'demo']);

        return ChannelConnection::query()->create([
            'branch_id' => 12,
            'channel_id' => $channel->id,
            'name' => 'Connection',
            'credentials' => [],
            'status' => 'active',
        ]);
    }

    private function setBranch(int|string|null $branchId): void
    {
        $this->app->instance(BranchContextResolverContract::class, new class($branchId) implements BranchContextResolverContract
        {
            public function __construct(private readonly int|string|null $branchId) {}

            public function branchId(): int|string|null
            {
                return $this->branchId;
            }
        });
        $this->app->forgetInstance(OmnichannelContext::class);
    }
}

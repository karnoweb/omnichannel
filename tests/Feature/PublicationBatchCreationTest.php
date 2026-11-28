<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Tests\Feature;

use Illuminate\Support\Str;
use Karnoweb\Omnichannel\Contracts\BranchContextResolverContract;
use Karnoweb\Omnichannel\Contracts\ProductRepository;
use Karnoweb\Omnichannel\Data\CreatePublicationBatchData;
use Karnoweb\Omnichannel\Data\PublicationTargetData;
use Karnoweb\Omnichannel\Enums\PublicationSourceType;
use Karnoweb\Omnichannel\Enums\PublicationType;
use Karnoweb\Omnichannel\Models\Channel;
use Karnoweb\Omnichannel\Models\ChannelConnection;
use Karnoweb\Omnichannel\Services\ArrayProductRepository;
use Karnoweb\Omnichannel\Services\PublicationBatchService;
use Karnoweb\Omnichannel\Support\OmnichannelContext;
use Karnoweb\Omnichannel\Tests\TestCase;
use Karnoweb\Omnichannel\ValueObjects\PublicationContent;

final class PublicationBatchCreationTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('h', 32)));
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations_squashed');
    }

    public function test_batch_creates_one_publication_per_target(): void
    {
        $this->setBranch(8);
        $context = app(OmnichannelContext::class);
        $this->app->instance(ProductRepository::class, new ArrayProductRepository([
            ['id' => 'sku-88', 'branch_id' => 8, 'name' => 'Batch item'],
        ], $context));
        [$a, $b] = $this->connections(8);
        $content = PublicationContent::fromArray(['caption' => 'Multi']);
        $batch = app(PublicationBatchService::class)->create(new CreatePublicationBatchData(
            sourceType: PublicationSourceType::Product,
            baseContent: $content,
            targets: [
                new PublicationTargetData($a->id, PublicationType::Post),
                new PublicationTargetData($b->id, PublicationType::Post, sortOrder: 1),
            ],
            productId: 'sku-88',
            intentId: 'batch-intent-1',
        ));
        self::assertCount(2, $batch->items);
        self::assertSame('sku-88', $batch->items->first()?->publication?->product_id);
    }

    /**
     * @return array{ChannelConnection, ChannelConnection}
     */
    private function connections(int $branchId): array
    {
        $channel = Channel::query()->create(['name' => 'Demo', 'slug' => 'demo-'.Str::random(4), 'driver' => 'demo']);
        $a = ChannelConnection::query()->create(['branch_id' => $branchId, 'channel_id' => $channel->id, 'name' => 'A', 'credentials' => [], 'status' => 'active']);
        $b = ChannelConnection::query()->create(['branch_id' => $branchId, 'channel_id' => $channel->id, 'name' => 'B', 'credentials' => [], 'status' => 'active']);

        return [$a, $b];
    }

    private function setBranch(int $branchId): void
    {
        $this->app->instance(BranchContextResolverContract::class, new class($branchId) implements BranchContextResolverContract
        {
            public function __construct(private readonly int $branchId) {}

            public function branchId(): int|string|null
            {
                return $this->branchId;
            }
        });
        $this->app->forgetInstance(OmnichannelContext::class);
    }
}

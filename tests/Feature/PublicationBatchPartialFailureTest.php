<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Karnoweb\Omnichannel\Contracts\BranchContextResolverContract;
use Karnoweb\Omnichannel\Contracts\ProductRepository;
use Karnoweb\Omnichannel\Data\CreatePublicationBatchData;
use Karnoweb\Omnichannel\Data\PublicationTargetData;
use Karnoweb\Omnichannel\Enums\PublicationBatchStatus;
use Karnoweb\Omnichannel\Enums\PublicationSourceType;
use Karnoweb\Omnichannel\Enums\PublicationStatus;
use Karnoweb\Omnichannel\Enums\PublicationType;
use Karnoweb\Omnichannel\Models\Channel;
use Karnoweb\Omnichannel\Models\ChannelConnection;
use Karnoweb\Omnichannel\Services\ArrayProductRepository;
use Karnoweb\Omnichannel\Services\PublicationBatchService;
use Karnoweb\Omnichannel\Support\OmnichannelContext;
use Karnoweb\Omnichannel\Tests\TestCase;
use Karnoweb\Omnichannel\ValueObjects\PublicationContent;

final class PublicationBatchPartialFailureTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('i', 32)));
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations_squashed');
    }

    public function test_batch_status_becomes_partially_completed_when_one_target_fails(): void
    {
        $this->setBranch(10);
        $context = app(OmnichannelContext::class);
        $this->app->instance(ProductRepository::class, new ArrayProductRepository([
            ['id' => 'sku-pf', 'branch_id' => 10, 'name' => 'PF'],
        ], $context));
        [$a, $b] = $this->connections(10);
        $batch = app(PublicationBatchService::class)->create(new CreatePublicationBatchData(
            sourceType: PublicationSourceType::Product,
            baseContent: PublicationContent::fromArray(['caption' => 'PF']),
            targets: [
                new PublicationTargetData($a->id, PublicationType::Post),
                new PublicationTargetData($b->id, PublicationType::Post),
            ],
            productId: 'sku-pf',
        ));
        $items = $batch->items->values();
        $items[0]->publication?->transitionTo(PublicationStatus::Publishing);
        $items[0]->publication?->transitionTo(PublicationStatus::Published);
        $items[1]->publication?->transitionTo(PublicationStatus::Publishing);
        $items[1]->publication?->transitionTo(PublicationStatus::Failed);
        $service = app(PublicationBatchService::class);
        $service->dispatch($batch, mockOnly: true);
        $updated = $service->refreshStatus($batch->refresh());
        self::assertSame(PublicationBatchStatus::PartiallyCompleted, $updated->status);
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
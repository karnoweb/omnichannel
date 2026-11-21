<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Karnoweb\Omnichannel\Contracts\BranchContextResolverContract;
use Karnoweb\Omnichannel\Data\CreatePublicationBatchData;
use Karnoweb\Omnichannel\Data\PublicationTargetData;
use Karnoweb\Omnichannel\Enums\PublicationSourceType;
use Karnoweb\Omnichannel\Enums\PublicationType;
use Karnoweb\Omnichannel\Models\Channel;
use Karnoweb\Omnichannel\Models\ChannelConnection;
use Karnoweb\Omnichannel\Services\PublicationBatchService;
use Karnoweb\Omnichannel\Support\OmnichannelContext;
use Karnoweb\Omnichannel\Tests\TestCase;
use Karnoweb\Omnichannel\ValueObjects\PublicationContent;
use RuntimeException;

final class PublicationBatchValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('j', 32)));
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations_squashed');
    }

    public function test_product_batch_without_product_id_is_rejected(): void
    {
        $this->setBranch(9);
        $channel = Channel::query()->create(['name' => 'Demo', 'slug' => 'demo', 'driver' => 'demo']);
        $connection = ChannelConnection::query()->create(['branch_id' => 9, 'channel_id' => $channel->id, 'name' => 'C', 'credentials' => [], 'status' => 'active']);
        $this->expectException(RuntimeException::class);
        app(PublicationBatchService::class)->create(new CreatePublicationBatchData(
            sourceType: PublicationSourceType::Product,
            baseContent: PublicationContent::fromArray(['caption' => 'x']),
            targets: [new PublicationTargetData($connection->id, PublicationType::Post)],
        ));
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
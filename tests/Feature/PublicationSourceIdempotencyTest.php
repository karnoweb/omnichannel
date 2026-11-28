<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Karnoweb\Omnichannel\Contracts\BranchContextResolverContract;
use Karnoweb\Omnichannel\Contracts\ProductRepository;
use Karnoweb\Omnichannel\Enums\PublicationType;
use Karnoweb\Omnichannel\Models\Channel;
use Karnoweb\Omnichannel\Models\ChannelConnection;
use Karnoweb\Omnichannel\Services\ArrayProductRepository;
use Karnoweb\Omnichannel\Services\PublishingService;
use Karnoweb\Omnichannel\Support\OmnichannelContext;
use Karnoweb\Omnichannel\Tests\TestCase;
use Karnoweb\Omnichannel\ValueObjects\PublicationContent;

final class PublicationSourceIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('c', 32)));
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations_squashed');
    }

    public function test_duplicate_free_form_create_returns_same_publication(): void
    {
        $this->setBranch(2);
        $connection = $this->connection();
        $content = PublicationContent::fromArray(['caption' => 'Notice']);
        $service = app(PublishingService::class);
        $first = $service->createFromContent($connection, PublicationType::Post, $content, 'Notice');
        $second = $service->createFromContent($connection, PublicationType::Post, $content, 'Notice');
        self::assertSame($first->id, $second->id);
    }

    public function test_free_form_and_product_with_same_caption_do_not_share_idempotency(): void
    {
        $this->setBranch(2);
        $context = app(OmnichannelContext::class);
        $this->app->instance(ProductRepository::class, new ArrayProductRepository([
            ['id' => 'p1', 'branch_id' => 2, 'name' => 'Item'],
        ], $context));
        $connection = $this->connection();
        $content = PublicationContent::fromArray(['caption' => 'shared-text']);
        $free = app(PublishingService::class)->createFromContent($connection, PublicationType::Post, $content);
        $product = app(PublishingService::class)->create('p1', $connection, PublicationType::Post, $content);
        self::assertNotSame($free->idempotency_key, $product->idempotency_key);
    }

    private function connection(): ChannelConnection
    {
        $channel = Channel::query()->create(['name' => 'Demo', 'slug' => 'demo', 'driver' => 'demo']);

        return ChannelConnection::query()->create([
            'branch_id' => 2,
            'channel_id' => $channel->id,
            'name' => 'Conn',
            'credentials' => [],
            'status' => 'active',
        ]);
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

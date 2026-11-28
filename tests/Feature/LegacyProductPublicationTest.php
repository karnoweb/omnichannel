<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Karnoweb\Omnichannel\Contracts\BranchContextResolverContract;
use Karnoweb\Omnichannel\Contracts\ProductRepository;
use Karnoweb\Omnichannel\Enums\PublicationSourceType;
use Karnoweb\Omnichannel\Enums\PublicationType;
use Karnoweb\Omnichannel\Models\Channel;
use Karnoweb\Omnichannel\Models\ChannelConnection;
use Karnoweb\Omnichannel\Services\ArrayProductRepository;
use Karnoweb\Omnichannel\Services\PublishingService;
use Karnoweb\Omnichannel\Support\OmnichannelContext;
use Karnoweb\Omnichannel\Tests\TestCase;
use Karnoweb\Omnichannel\ValueObjects\PublicationContent;

final class LegacyProductPublicationTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('b', 32)));
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations_squashed');
    }

    public function test_legacy_create_still_binds_product_and_source_type(): void
    {
        $this->setBranch(7);
        $context = app(OmnichannelContext::class);
        $this->app->instance(ProductRepository::class, new ArrayProductRepository([
            ['id' => 'sku-1', 'branch_id' => 7, 'name' => 'Widget'],
        ], $context));
        $connection = $this->connection();
        $content = new PublicationContent(caption: 'Launch');

        $publication = app(PublishingService::class)->create('sku-1', $connection, PublicationType::Post, $content);

        self::assertSame(PublicationSourceType::Product, $publication->source_type);
        self::assertSame('sku-1', $publication->product_id);
        self::assertSame('Widget', $publication->product_name);
        self::assertSame('Widget', $publication->source_title);
    }

    private function connection(): ChannelConnection
    {
        $channel = Channel::query()->create(['name' => 'Demo', 'slug' => 'demo', 'driver' => 'demo']);

        return ChannelConnection::query()->create([
            'branch_id' => 7,
            'channel_id' => $channel->id,
            'name' => 'Connection',
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

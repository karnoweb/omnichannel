<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Tests\Feature;

use Karnoweb\Omnichannel\Contracts\BranchContextResolverContract;
use Karnoweb\Omnichannel\Enums\PublicationStatus;
use Karnoweb\Omnichannel\Enums\PublicationType;
use Karnoweb\Omnichannel\Exceptions\BranchAccessDeniedException;
use Karnoweb\Omnichannel\Exceptions\ProductNotFoundException;
use Karnoweb\Omnichannel\Jobs\PublishPublicationJob;
use Karnoweb\Omnichannel\Models\Channel;
use Karnoweb\Omnichannel\Models\ChannelConnection;
use Karnoweb\Omnichannel\Models\Publication;
use Karnoweb\Omnichannel\Services\ArrayProductRepository;
use Karnoweb\Omnichannel\Services\PublishingService;
use Karnoweb\Omnichannel\Support\OmnichannelContext;
use Karnoweb\Omnichannel\Tests\TestCase;
use Karnoweb\Omnichannel\ValueObjects\PublicationContent;

final class BranchIsolationTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        $app['config']->set('omnichannel.general.prefix', 'omc_');
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations_squashed');
    }

    public function test_branch_scope_is_exact_and_explicit_cross_branch_selection_is_denied(): void
    {
        $this->setBranch(11);
        $query = ChannelConnection::query()->forBranch();

        self::assertStringContainsString('branch_id', $query->toSql());
        self::assertSame([11], $query->getBindings());
        $this->expectException(BranchAccessDeniedException::class);
        ChannelConnection::query()->forBranch(22);
    }

    public function test_missing_branch_context_fails_closed_and_system_scope_is_explicit(): void
    {
        $this->setBranch(null);
        try {
            ChannelConnection::query()->forBranch();
            self::fail('A missing branch context must be rejected.');
        } catch (BranchAccessDeniedException) {
            self::assertTrue(true);
        }

        $context = app(OmnichannelContext::class);
        $query = $context->runAsSystem(fn () => ChannelConnection::query()->forBranch());
        self::assertSame([], $query->getBindings());
        self::assertFalse($context->isSystemContext());
    }

    public function test_context_overrides_are_restored_even_when_work_throws(): void
    {
        $this->setBranch(11);
        $context = app(OmnichannelContext::class);

        try {
            $context->runForBranch(22, static function (): void {
                self::assertSame(22, app(OmnichannelContext::class)->branchId());
                throw new \RuntimeException('expected');
            });
        } catch (\RuntimeException) {
            self::assertSame(11, $context->branchId());
        }
    }

    public function test_array_product_repository_returns_only_the_requested_branch_product(): void
    {
        $repository = new ArrayProductRepository([
            ['id' => 'p1', 'name' => 'Branch one', 'branch_id' => 11],
            ['id' => 'p2', 'name' => 'Branch two', 'branch_id' => 22],
        ]);

        self::assertSame('p1', $repository->findOrFailForBranch('p1', 11)->id);
        $this->expectException(ProductNotFoundException::class);
        $repository->findOrFailForBranch('p1', 22);
    }

    public function test_array_product_repository_does_not_cache_products_across_branch_contexts(): void
    {
        $this->setBranch(11);
        $context = app(OmnichannelContext::class);
        $repository = new ArrayProductRepository([
            ['id' => 'p1', 'name' => 'Branch one', 'branch_id' => 11],
            ['id' => 'p2', 'name' => 'Branch two', 'branch_id' => 22],
        ], $context);

        self::assertSame(['p1'], array_map(static fn ($product): string => $product->id, $repository->all()));
        $branchTwoProducts = $context->runForBranch(22, fn (): array => $repository->all());
        self::assertSame(['p2'], array_map(static fn ($product): string => $product->id, $branchTwoProducts));
    }

    public function test_publication_service_rejects_a_connection_from_another_branch(): void
    {
        $this->setBranch(11);
        $connection = new ChannelConnection;
        $connection->setAttribute('branch_id', 22);

        $this->expectException(BranchAccessDeniedException::class);
        app(PublishingService::class)->create(
            'p1',
            $connection,
            PublicationType::Post,
            new PublicationContent(caption: 'test'),
        );
    }

    public function test_publication_service_rejects_a_publication_from_another_branch(): void
    {
        $this->setBranch(11);
        $publication = new Publication;
        $publication->setAttribute('branch_id', 22);

        $this->expectException(BranchAccessDeniedException::class);
        app(PublishingService::class)->publish($publication);
    }

    public function test_queue_job_runs_under_the_publication_branch_and_restores_context(): void
    {
        $this->setBranch(11);
        $channel = Channel::query()->create(['name' => 'Demo', 'slug' => 'demo', 'driver' => 'demo']);
        $connection = ChannelConnection::query()->create([
            'branch_id' => 22,
            'channel_id' => $channel->id,
            'name' => 'Connection',
            'credentials' => [],
            'status' => 'active',
        ]);
        $publication = Publication::factory()->create([
            'branch_id' => 22,
            'channel_id' => $channel->id,
            'channel_connection_id' => $connection->id,
            'status' => PublicationStatus::Draft,
        ]);
        $context = app(OmnichannelContext::class);
        (new PublishPublicationJob($publication))->handle(app(PublishingService::class), $context);
        self::assertSame(11, $context->branchId());
        self::assertSame(PublicationStatus::Published, $publication->fresh()->status);
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

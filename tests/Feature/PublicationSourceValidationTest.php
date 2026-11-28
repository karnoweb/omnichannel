<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Tests\Feature;

use Karnoweb\Omnichannel\Contracts\BranchContextResolverContract;
use Karnoweb\Omnichannel\Contracts\PublicationSourceResolverContract;
use Karnoweb\Omnichannel\Data\CreatePublicationData;
use Karnoweb\Omnichannel\Enums\PublicationSourceType;
use Karnoweb\Omnichannel\Enums\PublicationStatus;
use Karnoweb\Omnichannel\Enums\PublicationType;
use Karnoweb\Omnichannel\Models\Channel;
use Karnoweb\Omnichannel\Models\ChannelConnection;
use Karnoweb\Omnichannel\Models\Publication;
use Karnoweb\Omnichannel\Services\PublicationCreationService;
use Karnoweb\Omnichannel\Services\PublicationSourceRegistry;
use Karnoweb\Omnichannel\Support\OmnichannelContext;
use Karnoweb\Omnichannel\Tests\TestCase;
use Karnoweb\Omnichannel\ValueObjects\PublicationContent;
use RuntimeException;

final class PublicationSourceValidationTest extends TestCase
{

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('b', 32)));
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations_squashed');
    }

    public function test_reference_source_without_resolver_is_rejected(): void
    {
        $connection = $this->connection();
        $this->setBranch(4);
        $data = new CreatePublicationData(
            sourceType: PublicationSourceType::Reference,
            connection: $connection,
            type: PublicationType::Post,
            content: PublicationContent::fromArray(['caption' => 'x']),
            sourceReferenceType: 'blog_post',
            sourceReferenceId: '99',
        );
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No resolver registered');
        app(PublicationCreationService::class)->create($data);
    }

    public function test_reference_source_with_registered_resolver_creates_publication(): void
    {
        $connection = $this->connection();
        $this->setBranch(4);
        app(PublicationSourceRegistry::class)->register(new class implements PublicationSourceResolverContract
        {
            public function logicalType(): string
            {
                return 'blog_post';
            }

            public function resolveSnapshot(
                string $referenceType,
                int|string $referenceId,
                int|string $branchId,
            ): ?PublicationContent {
                return PublicationContent::fromArray(['caption' => 'resolved']);
            }

            public function resolveTitle(
                string $referenceType,
                int|string $referenceId,
                int|string $branchId,
            ): ?string {
                return 'Title';
            }
        });
        $data = new CreatePublicationData(
            sourceType: PublicationSourceType::Reference,
            connection: $connection,
            type: PublicationType::Post,
            content: PublicationContent::fromArray(['caption' => 'x']),
            sourceReferenceType: 'blog_post',
            sourceReferenceId: '99',
        );
        $publication = app(PublicationCreationService::class)->create($data);
        $this->assertInstanceOf(Publication::class, $publication);
        $this->assertSame(PublicationStatus::Draft, $publication->status);
        $this->assertSame('blog_post', $publication->source_reference_type);
        $this->assertSame('99', $publication->source_reference_id);
    }

    public function test_product_source_requires_product_id(): void
    {
        $connection = $this->connection();
        $this->setBranch(4);
        $data = new CreatePublicationData(
            sourceType: PublicationSourceType::Product,
            connection: $connection,
            type: PublicationType::Post,
            content: PublicationContent::fromArray(['caption' => 'x']),
            productId: null,
        );
        $this->expectException(RuntimeException::class);
        app(PublicationCreationService::class)->create($data);
    }

    private function connection(): ChannelConnection
    {
        $channel = Channel::query()->create(['name' => 'Demo', 'slug' => 'demo', 'driver' => 'demo']);

        return ChannelConnection::query()->create([
            'branch_id' => 4,
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
        app(OmnichannelContext::class)->runForBranch($branchId, static fn (): null => null);
    }
}

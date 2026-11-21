<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Str;
use Karnoweb\Omnichannel\Contracts\BranchContextResolverContract;
use Karnoweb\Omnichannel\Enums\PublicationSourceType;
use Karnoweb\Omnichannel\Enums\PublicationType;
use Karnoweb\Omnichannel\Exceptions\UnsupportedCapabilityException;
use Karnoweb\Omnichannel\Models\Channel;
use Karnoweb\Omnichannel\Models\ChannelConnection;
use Karnoweb\Omnichannel\Services\PublishingService;
use Karnoweb\Omnichannel\Support\OmnichannelContext;
use Karnoweb\Omnichannel\Tests\TestCase;
use Karnoweb\Omnichannel\ValueObjects\PublicationContent;

final class FreeFormPublicationTest extends TestCase
{
    use DatabaseMigrations;

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations_squashed');
    }

    public function test_free_form_post_can_be_created_without_product(): void
    {
        [$connection] = $this->demoConnection(3);
        $content = PublicationContent::fromArray(['caption' => 'Hello omnichannel']);
        $publication = $this->withinBranch(3, fn () => app(PublishingService::class)->createFromContent(
            $connection,
            PublicationType::Post,
            $content,
            sourceTitle: 'Weekly update',
        ));
        self::assertSame(PublicationSourceType::FreeForm, $publication->source_type);
        self::assertNull($publication->product_id);
        self::assertSame('Weekly update', $publication->source_title);
    }

    public function test_free_form_product_type_is_rejected_on_marketplace_drivers(): void
    {
        [$connection] = $this->basalamConnection(3);
        $content = PublicationContent::fromArray(['listing_title' => 'X']);
        $this->expectException(UnsupportedCapabilityException::class);
        $this->withinBranch(3, fn () => app(PublishingService::class)->createFromContent($connection, PublicationType::Product, $content));
    }

    /**
     * @return array{ChannelConnection}
     */
    private function demoConnection(int $branchId): array
    {
        $channel = Channel::query()->create(['name' => 'Demo', 'slug' => 'demo-'.Str::random(6), 'driver' => 'demo']);
        $connection = ChannelConnection::query()->create([
            'branch_id' => $branchId,
            'channel_id' => $channel->id,
            'name' => 'Demo conn',
            'credentials' => [],
            'status' => 'active',
        ]);

        return [$connection];
    }

    /**
     * @return array{ChannelConnection}
     */
    private function basalamConnection(int $branchId): array
    {
        $channel = Channel::query()->create(['name' => 'Basalam', 'slug' => 'basalam-'.Str::random(6), 'driver' => 'basalam']);
        $connection = ChannelConnection::query()->create([
            'branch_id' => $branchId,
            'channel_id' => $channel->id,
            'name' => 'Basalam conn',
            'credentials' => ['access_token' => 'token'],
            'status' => 'active',
        ]);

        return [$connection];
    }

    private function withinBranch(int $branchId, callable $callback): mixed
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

        return app(OmnichannelContext::class)->runForBranch($branchId, $callback);
    }
}
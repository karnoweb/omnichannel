<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Tests\Feature;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Karnoweb\Omnichannel\Contracts\BranchContextResolverContract;
use Karnoweb\Omnichannel\Enums\FailureClass;
use Karnoweb\Omnichannel\Enums\PublicationStatus;
use Karnoweb\Omnichannel\Enums\PublicationType;
use Karnoweb\Omnichannel\Models\Channel;
use Karnoweb\Omnichannel\Models\ChannelConnection;
use Karnoweb\Omnichannel\Models\Publication;
use Karnoweb\Omnichannel\Services\PublishingService;
use Karnoweb\Omnichannel\Support\OmnichannelContext;
use Karnoweb\Omnichannel\Tests\TestCase;

final class PublicationDeactivationTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $app['config']->set('omnichannel.channels.basalam.base_url', 'https://basalam.test');
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations_squashed');
    }

    public function test_delete_is_unsupported_but_deactivate_is_logged_and_keeps_publication(): void
    {
        $this->setBranch(12);
        [$publication] = $this->publication();
        $confirmedContent = $publication->content;
        Http::fake(fn () => Http::response(['id' => 101, 'status' => 'draft'], 200));
        $service = app(PublishingService::class);

        $delete = $service->delete($publication);
        self::assertFalse($delete->successful);
        self::assertSame(FailureClass::Unsupported, $delete->failureClass);
        Http::assertNothingSent();

        $deactivation = $service->deactivate($publication->fresh());
        $fresh = $publication->fresh();

        self::assertTrue($deactivation->successful);
        self::assertSame(PublicationStatus::Published, $fresh->status);
        self::assertSame('remote-101', $fresh->external_id);
        self::assertSame($confirmedContent, $fresh->content);
        self::assertSame('deactivated', data_get($fresh->metadata, 'omnichannel.deactivation.status'));
        self::assertNull($fresh->attempt_token);
        self::assertSame('deactivate', $fresh->logs()->latest('id')->first()?->operation->value);
        self::assertSame('succeeded', $fresh->logs()->latest('id')->first()?->status->value);
        Http::assertSent(fn ($request): bool => str_contains($request->url(), '/v1/products/remote-101')
            && $request->method() === 'PATCH');
    }

    /** @return array{Publication, ChannelConnection} */
    private function publication(): array
    {
        $channel = Channel::query()->create([
            'name' => 'Basalam', 'slug' => 'basalam-'.Str::random(8), 'driver' => 'basalam',
        ]);
        $connection = ChannelConnection::query()->create([
            'branch_id' => 12,
            'channel_id' => $channel->id,
            'name' => 'Basalam test connection',
            'credentials' => ['access_token' => 'fake-access-token'],
            'status' => 'active',
        ]);
        $publication = Publication::factory()->published()->create([
            'branch_id' => 12,
            'channel_id' => $channel->id,
            'channel_connection_id' => $connection->id,
            'type' => PublicationType::Product,
            'content' => [
                'title' => 'Sample product', 'caption' => 'Description', 'media' => [],
                'hashtags' => [], 'extra' => [],
            ],
            'external_id' => 'remote-101',
            'external_url' => 'https://basalam.test/product/remote-101',
        ]);

        return [$publication, $connection];
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

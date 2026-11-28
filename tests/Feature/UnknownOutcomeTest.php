<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Karnoweb\Omnichannel\Contracts\BranchContextResolverContract;
use Karnoweb\Omnichannel\Enums\FailureClass;
use Karnoweb\Omnichannel\Enums\LogOperation;
use Karnoweb\Omnichannel\Enums\PublicationStatus;
use Karnoweb\Omnichannel\Jobs\PublishPublicationJob;
use Karnoweb\Omnichannel\Models\Channel;
use Karnoweb\Omnichannel\Models\ChannelConnection;
use Karnoweb\Omnichannel\Models\Publication;
use Karnoweb\Omnichannel\Models\PublicationLog;
use Karnoweb\Omnichannel\Services\ChannelManager;
use Karnoweb\Omnichannel\Services\PublishingService;
use Karnoweb\Omnichannel\Support\OmnichannelContext;
use Karnoweb\Omnichannel\Tests\TestCase;

final class UnknownOutcomeTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations_squashed');
    }

    public function test_demo_driver_lifecycle_uses_no_external_transport(): void
    {
        $this->setBranch(12);
        [$connection, $publication] = $this->publication([]);
        $connection->forceFill(['credentials' => ['access_token' => 'sample-token']])->save();
        Http::preventStrayRequests();
        Http::fake([]);
        $driver = app(ChannelManager::class)->driverForPublication($publication);

        self::assertTrue($driver->testConnection($connection)->successful);
        self::assertTrue($driver->validate($publication)->passes());
        $published = $driver->publish($publication);
        self::assertTrue($published->successful);
        self::assertNotNull($published->externalId);

        $publication->forceFill(['external_id' => $published->externalId])->save();
        self::assertSame($published->externalId, $driver->update($publication)->externalId);
        self::assertTrue($driver->delete($publication)->successful);
        Http::assertNothingSent();
    }

    public function test_uncertain_publish_is_persisted_and_manual_retry_requires_confirmation(): void
    {
        $this->setBranch(12);
        Queue::fake();
        [$connection, $publication] = $this->publication(['demo' => ['force' => FailureClass::UnknownOutcome->value]]);

        $result = app(PublishingService::class)->publish($publication);

        self::assertFalse($result->isRetryable());
        self::assertSame(FailureClass::UnknownOutcome, $result->failureClass);
        self::assertSame(PublicationStatus::UnknownOutcome, $publication->fresh()->status);
        self::assertSame(FailureClass::UnknownOutcome, $publication->fresh()->failure_class);

        try {
            app(PublishingService::class)->retry($publication->fresh());
            self::fail('An unknown external outcome must require an explicit confirmation.');
        } catch (\RuntimeException $exception) {
            self::assertStringContainsString('explicitly confirm', $exception->getMessage());
        }

        app(PublishingService::class)->retry($publication->fresh(), confirmUnknownOutcome: true);

        self::assertSame(PublicationStatus::Failed, $publication->fresh()->status);
        self::assertSame(1, PublicationLog::query()->where('publication_id', $publication->id)->where('operation', LogOperation::ManualRetry)->count());
        Queue::assertPushed(PublishPublicationJob::class);
    }

    public function test_cache_loss_does_not_republish_a_durable_success(): void
    {
        $this->setBranch(12);
        [$connection, $publication] = $this->publication([]);

        $service = app(PublishingService::class);
        self::assertTrue($service->publish($publication)->successful);
        Cache::flush();

        $replay = $service->publish($publication->fresh());

        self::assertTrue($replay->successful);
        self::assertSame('already_published', $replay->metadata['skipped']);
        self::assertSame(1, $publication->fresh()->attempts);

        $driverReplay = app(ChannelManager::class)->driverForPublication($publication->fresh())->publish($publication->fresh());
        self::assertSame($publication->fresh()->external_id, $driverReplay->externalId);
        self::assertTrue($driverReplay->metadata['idempotent_replay']);
    }

    public function test_atomic_claim_allows_only_one_concurrent_publisher(): void
    {
        $this->setBranch(12);
        [, $publication] = $this->publication([]);
        $first = $publication->fresh();
        $second = $publication->fresh();

        self::assertTrue($first->claimForPublishing());
        self::assertFalse($second->claimForPublishing());
        self::assertSame(1, $publication->fresh()->attempts);
    }

    /** @param array<string, mixed> $metadata */
    private function publication(array $metadata): array
    {
        $channel = Channel::query()->create(['name' => 'Demo', 'slug' => 'demo', 'driver' => 'demo']);
        $connection = ChannelConnection::query()->create([
            'branch_id' => 12,
            'channel_id' => $channel->id,
            'name' => 'Demo connection',
            'credentials' => [],
            'status' => 'active',
        ]);
        $publication = Publication::factory()->create([
            'branch_id' => 12,
            'channel_id' => $channel->id,
            'channel_connection_id' => $connection->id,
            'status' => PublicationStatus::Draft,
            'metadata' => $metadata,
        ]);

        return [$connection, $publication];
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

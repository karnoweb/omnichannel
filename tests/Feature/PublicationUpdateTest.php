<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Tests\Feature;

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
use Karnoweb\Omnichannel\ValueObjects\PublicationContent;

final class PublicationUpdateTest extends TestCase
{

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations_squashed');
    }

    public function test_invalid_proposal_is_validated_without_changing_confirmed_content(): void
    {
        $this->setBranch(12);
        $publication = $this->publishedPublication();
        $confirmed = $publication->content()->toArray();

        $result = app(PublishingService::class)->update(
            $publication,
            PublicationContent::fromArray(['caption' => 'Invalid because media is missing']),
        );

        self::assertFalse($result->successful);
        self::assertSame(FailureClass::Validation, $result->failureClass);
        self::assertSame($confirmed, $publication->fresh()->content()->toArray());
        self::assertSame(PublicationStatus::Published, $publication->fresh()->status);
    }

    public function test_definite_driver_failure_preserves_confirmed_content(): void
    {
        $this->setBranch(12);
        $publication = $this->publishedPublication(['demo' => ['force' => FailureClass::Validation->value]]);
        $confirmed = $publication->content()->toArray();

        $result = app(PublishingService::class)->update($publication, $this->newContent('Rejected change'));

        self::assertFalse($result->successful);
        self::assertSame(FailureClass::Validation, $result->failureClass);
        self::assertSame($confirmed, $publication->fresh()->content()->toArray());
        self::assertSame('failed', data_get($publication->fresh()->metadata, 'omnichannel.content_sync.status'));
    }

    public function test_successful_update_persists_new_content_and_sync_version(): void
    {
        $this->setBranch(12);
        $publication = $this->publishedPublication();
        $staleSnapshot = $publication->fresh();
        $proposal = $this->newContent('Confirmed change');

        $result = app(PublishingService::class)->update($publication, $proposal);
        $fresh = $publication->fresh();

        self::assertTrue($result->successful);
        self::assertSame($proposal->toArray(), $fresh->content()->toArray());
        self::assertSame('synced', data_get($fresh->metadata, 'omnichannel.content_sync.status'));
        self::assertSame(1, data_get($fresh->metadata, 'omnichannel.content_sync.content_version'));
        self::assertNull($fresh->attempt_token);

        self::assertFalse($staleSnapshot->claimForExternalMutation(
            'update',
            $this->newContent('Stale proposal')->toArray(),
            expectedContentVersion: 0,
        ));
        self::assertSame($proposal->toArray(), $publication->fresh()->content()->toArray());
    }

    public function test_ambiguous_update_keeps_confirmed_content_and_stores_pending_proposal(): void
    {
        $this->setBranch(12);
        $publication = $this->publishedPublication(['demo' => ['force' => FailureClass::UnknownOutcome->value]]);
        $confirmed = $publication->content()->toArray();
        $proposal = $this->newContent('Possibly applied change');

        $result = app(PublishingService::class)->update($publication, $proposal);
        $fresh = $publication->fresh();

        self::assertFalse($result->successful);
        self::assertSame(FailureClass::UnknownOutcome, $result->failureClass);
        self::assertSame($confirmed, $fresh->content()->toArray());
        self::assertSame('unknown_outcome', data_get($fresh->metadata, 'omnichannel.content_sync.status'));
        self::assertSame($proposal->toArray(), data_get($fresh->metadata, 'omnichannel.content_sync.pending_content'));
        self::assertNotNull($fresh->attempt_token);
        self::assertSame('update', data_get($fresh->metadata, 'omnichannel.active_operation.type'));
        self::assertFalse($fresh->claimForExternalMutation('delete'));
    }

    public function test_unsupported_field_changes_and_concurrent_mutations_are_rejected(): void
    {
        $this->setBranch(12);
        $instagram = $this->publishedPublication([], 'instagram');
        $changedMedia = PublicationContent::fromArray([
            ...$instagram->content()->toArray(),
            'media' => [['type' => 'image', 'url' => 'https://example.test/other.jpg']],
        ]);

        $unsupported = app(PublishingService::class)->update($instagram, $changedMedia);

        self::assertFalse($unsupported->successful);
        self::assertSame(FailureClass::Unsupported, $unsupported->failureClass);
        self::assertSame('https://example.test/image.jpg', $instagram->fresh()->content()->firstMedia()?->url);
        self::assertNull($instagram->fresh()->attempt_token);

        $publication = $this->publishedPublication();
        self::assertTrue($publication->claimForExternalMutation('update', $this->newContent('In progress')->toArray()));

        $update = app(PublishingService::class)->update($publication->fresh(), $this->newContent('Competing update'));
        $delete = app(PublishingService::class)->delete($publication->fresh());
        $publish = app(PublishingService::class)->publish($publication->fresh());

        self::assertSame(FailureClass::Transient, $update->failureClass);
        self::assertSame(FailureClass::Transient, $delete->failureClass);
        self::assertSame(FailureClass::Transient, $publish->failureClass);
        self::assertSame(PublicationStatus::Published, $publication->fresh()->status);
        self::assertNotNull($publication->fresh()->attempt_token);
    }

    /** @param array<string, mixed> $metadata */
    private function publishedPublication(array $metadata = [], string $driver = 'demo'): Publication
    {
        $this->setBranch(12);
        $channel = Channel::query()->create([
            'name' => ucfirst($driver),
            'slug' => $driver.'-'.Str::random(8),
            'driver' => $driver,
        ]);
        $connection = ChannelConnection::query()->create([
            'branch_id' => 12,
            'channel_id' => $channel->id,
            'name' => ucfirst($driver).' connection',
            'credentials' => $driver === 'instagram' ? ['access_token' => 'fake-token'] : [],
            'status' => 'active',
        ]);

        return Publication::factory()->published()->create([
            'branch_id' => 12,
            'channel_id' => $channel->id,
            'channel_connection_id' => $connection->id,
            'type' => PublicationType::Post,
            'content' => $this->newContent('Previously confirmed')->toArray(),
            'external_id' => 'external-123',
            'external_url' => 'https://example.test/post/123',
            'metadata' => $metadata,
        ]);
    }

    private function newContent(string $caption): PublicationContent
    {
        return PublicationContent::fromArray([
            'caption' => $caption,
            'media' => [['type' => 'image', 'url' => 'https://example.test/image.jpg']],
            'hashtags' => ['new-tag'],
            'extra' => [],
        ]);
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

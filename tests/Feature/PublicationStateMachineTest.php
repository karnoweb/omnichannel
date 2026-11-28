<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Karnoweb\Omnichannel\Contracts\BranchContextResolverContract;
use Karnoweb\Omnichannel\Enums\FailureClass;
use Karnoweb\Omnichannel\Enums\PublicationStatus;
use Karnoweb\Omnichannel\Enums\PublicationType;
use Karnoweb\Omnichannel\Events\PublicationPublished;
use Karnoweb\Omnichannel\Exceptions\InvalidStatusTransitionException;
use Karnoweb\Omnichannel\Exceptions\StalePublicationTransitionException;
use Karnoweb\Omnichannel\Jobs\PublishPublicationJob;
use Karnoweb\Omnichannel\Models\Channel;
use Karnoweb\Omnichannel\Models\ChannelConnection;
use Karnoweb\Omnichannel\Models\Publication;
use Karnoweb\Omnichannel\Services\PublishingService;
use Karnoweb\Omnichannel\Support\OmnichannelContext;
use Karnoweb\Omnichannel\Tests\TestCase;
use Karnoweb\Omnichannel\ValueObjects\PublicationContent;
use RuntimeException;

final class PublicationStateMachineTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations_squashed');
    }

    public function test_status_transition_table_rejects_terminal_and_invalid_moves(): void
    {
        self::assertTrue(PublicationStatus::Scheduled->canTransitionTo(PublicationStatus::Failed));
        self::assertTrue(PublicationStatus::Published->canTransitionTo(PublicationStatus::Deleted));
        self::assertFalse(PublicationStatus::Cancelled->canTransitionTo(PublicationStatus::Publishing));
        self::assertFalse(PublicationStatus::Deleted->canTransitionTo(PublicationStatus::Publishing));

        $publication = $this->publication(PublicationStatus::Cancelled);

        $this->expectException(InvalidStatusTransitionException::class);
        $publication->transitionTo(PublicationStatus::Publishing);
    }

    public function test_claim_increments_attempts_atomically_and_assigns_an_owner_token(): void
    {
        $publication = $this->publication(PublicationStatus::Draft);
        $first = $publication->fresh();
        $second = $publication->fresh();

        self::assertTrue($first->claimForPublishing());
        self::assertFalse($second->claimForPublishing());
        self::assertSame(1, $publication->fresh()->attempts);
        self::assertNotEmpty($publication->fresh()->attempt_token);
        self::assertArrayNotHasKey('attempt_token', $publication->fresh()->toArray());
    }

    public function test_invalid_scheduled_publication_fails_without_counting_a_send_attempt(): void
    {
        $this->setBranch(12);
        $publication = $this->publication(PublicationStatus::Scheduled, PublicationType::Carousel);

        $result = app(PublishingService::class)->publish($publication);
        $fresh = $publication->fresh();

        self::assertFalse($result->successful);
        self::assertSame(PublicationStatus::Failed, $fresh->status);
        self::assertSame(FailureClass::Validation, $fresh->failure_class);
        self::assertSame(0, $fresh->attempts);
        self::assertFalse($fresh->isDue());
        self::assertFalse(Publication::query()->due()->whereKey($fresh->id)->exists());
    }

    public function test_stale_transition_and_stale_attempt_cannot_overwrite_newer_state(): void
    {
        $scheduled = $this->publication(PublicationStatus::Scheduled);
        $older = $scheduled->fresh();
        $newer = $scheduled->fresh();

        $newer->transitionTo(PublicationStatus::Cancelled, ['scheduled_at' => null]);

        try {
            $older->transitionTo(PublicationStatus::Draft, ['scheduled_at' => null]);
            self::fail('A stale model must not overwrite a newer state.');
        } catch (InvalidStatusTransitionException|StalePublicationTransitionException) {
            self::assertSame(PublicationStatus::Cancelled, $scheduled->fresh()->status);
        }

        $retryable = $this->publication(PublicationStatus::Draft);
        $oldWorker = $retryable->fresh();
        self::assertTrue($oldWorker->claimForPublishing());
        $oldToken = $oldWorker->attempt_token;
        $oldAttemptSnapshot = clone $oldWorker;
        $oldWorker->markFailed(FailureClass::Transient, 'first.failed', 'First attempt failed.');

        $newWorker = $retryable->fresh();
        self::assertTrue($newWorker->claimForPublishing());
        self::assertNotSame($oldToken, $newWorker->attempt_token);

        $staleJob = new PublishPublicationJob($retryable->fresh(), $oldToken);
        $staleJob->failed(new RuntimeException('old worker stopped'));
        self::assertSame(PublicationStatus::Publishing, $retryable->fresh()->status);
        self::assertSame($newWorker->attempt_token, $retryable->fresh()->attempt_token);

        try {
            $oldAttemptSnapshot->markPublished('external-old');
            self::fail('A stale worker must not overwrite a newer attempt.');
        } catch (StalePublicationTransitionException) {
            self::assertSame(PublicationStatus::Publishing, $retryable->fresh()->status);
            self::assertSame($newWorker->attempt_token, $retryable->fresh()->attempt_token);
            self::assertSame(2, $retryable->fresh()->attempts);
        }
    }

    public function test_stuck_publishing_record_is_not_reclaimed_by_elapsed_time_or_job_failure(): void
    {
        $publication = $this->publication(PublicationStatus::Draft);
        self::assertTrue($publication->claimForPublishing());
        $publication->forceFill(['last_attempted_at' => now()->subHours(3)])->save();

        $duplicate = $publication->fresh();
        self::assertTrue(Publication::query()->stuckInFlight(now()->subHour())->whereKey($publication->id)->exists());
        self::assertFalse($duplicate->claimForPublishing());
        self::assertSame(PublicationStatus::Publishing, $publication->fresh()->status);
        self::assertSame(1, $publication->fresh()->attempts);

        $publication->forceFill(['last_attempted_at' => null])->save();
        self::assertTrue(Publication::query()->stuckInFlight(now()->subHour())->whereKey($publication->id)->exists());

        $job = new PublishPublicationJob($duplicate, $duplicate->attempt_token);
        $job->failed(new RuntimeException('worker stopped'));

        self::assertSame(PublicationStatus::UnknownOutcome, $publication->fresh()->status);
        self::assertSame(1, $publication->fresh()->attempts);
    }

    public function test_publication_event_is_dispatched_only_after_success_transaction_commits(): void
    {
        $this->setBranch(12);
        $publication = $this->publication(PublicationStatus::Draft);
        Event::fake([PublicationPublished::class]);

        DB::beginTransaction();
        $result = app(PublishingService::class)->publish($publication);

        self::assertTrue($result->successful, $result->errorCode.' '.$result->errorMessage);
        Event::assertNotDispatched(PublicationPublished::class);
        DB::commit();

        Event::assertDispatched(PublicationPublished::class);
        self::assertSame(PublicationStatus::Published, $publication->fresh()->status);
    }

    public function test_listener_failure_after_commit_does_not_repeat_external_publish(): void
    {
        $this->setBranch(12);
        $publication = $this->publication(PublicationStatus::Draft);
        Event::listen(PublicationPublished::class, static function (): never {
            throw new RuntimeException('listener failed');
        });

        try {
            app(PublishingService::class)->publish($publication);
            self::fail('The listener failure should reach the caller after the commit.');
        } catch (RuntimeException $exception) {
            self::assertSame('listener failed', $exception->getMessage());
        }

        self::assertSame(PublicationStatus::Published, $publication->fresh()->status);
        self::assertSame(1, $publication->fresh()->attempts);

        $replay = app(PublishingService::class)->publish($publication->fresh());

        self::assertTrue($replay->successful);
        self::assertSame('already_published', $replay->metadata['skipped']);
        self::assertSame(1, $publication->fresh()->attempts);
    }

    private function publication(
        PublicationStatus $status,
        PublicationType $type = PublicationType::Post,
    ): Publication {
        $this->setBranch(12);
        $channel = Channel::query()->create(['name' => 'Demo', 'slug' => 'demo-'.Str::random(8), 'driver' => 'demo']);
        $connection = ChannelConnection::query()->create([
            'branch_id' => 12,
            'channel_id' => $channel->id,
            'name' => 'Demo connection',
            'credentials' => [],
            'status' => 'active',
        ]);

        return Publication::factory()->create([
            'branch_id' => 12,
            'channel_id' => $channel->id,
            'channel_connection_id' => $connection->id,
            'status' => $status,
            'type' => $type,
            'content' => $type === PublicationType::Carousel
                ? ['caption' => 'Invalid album', 'media' => [], 'hashtags' => [], 'extra' => []]
                : PublicationContent::fromArray([
                    'caption' => 'Valid publication',
                    'media' => [['type' => 'image', 'url' => 'https://example.test/image.jpg']],
                    'hashtags' => [],
                    'extra' => [],
                ])->toArray(),
            'scheduled_at' => $status === PublicationStatus::Scheduled ? now()->addMinutes(10) : null,
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

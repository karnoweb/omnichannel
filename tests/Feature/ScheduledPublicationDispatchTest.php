<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Tests\Feature;

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Karnoweb\Omnichannel\Enums\PublicationStatus;
use Karnoweb\Omnichannel\Jobs\PublishPublicationJob;
use Karnoweb\Omnichannel\Models\Channel;
use Karnoweb\Omnichannel\Models\ChannelConnection;
use Karnoweb\Omnichannel\Models\Publication;
use Karnoweb\Omnichannel\Services\PublishingService;
use Karnoweb\Omnichannel\Support\OmnichannelContext;
use Karnoweb\Omnichannel\Tests\TestCase;

final class ScheduledPublicationDispatchTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations_squashed');
    }

    public function test_due_publication_is_reserved_and_not_queued_twice(): void
    {
        Cache::flush();
        Queue::fake();
        $this->duePublication();

        $this->artisan('omnichannel:dispatch-due')->assertExitCode(0);
        $this->artisan('omnichannel:dispatch-due')->assertExitCode(0);

        Queue::assertPushed(PublishPublicationJob::class, 1);
    }

    public function test_queued_scheduled_job_does_not_send_after_schedule_moves_to_future(): void
    {
        $publication = $this->duePublication();
        $job = new PublishPublicationJob($publication);
        $publication->forceFill(['scheduled_at' => now()->addHour()])->save();
        $job->handle(app(PublishingService::class), app(OmnichannelContext::class));

        self::assertSame(PublicationStatus::Scheduled, $publication->fresh()->status);
        self::assertSame(0, $publication->fresh()->attempts);
    }

    public function test_atomic_claim_rejects_a_schedule_moved_to_the_future(): void
    {
        $publication = $this->duePublication();
        Publication::query()->whereKey($publication->id)->update(['scheduled_at' => now()->addHour()]);

        self::assertFalse($publication->claimForPublishing());
        self::assertSame(0, $publication->fresh()->attempts);
    }

    public function test_history_upgrade_replaces_legacy_cascade_and_keeps_rows(): void
    {
        $publication = $this->duePublication();
        $migration = require __DIR__.'/../../database/migrations_squashed/2026_11_20_000002_preserve_publication_history.php';
        $migration->down();
        $repair = require __DIR__.'/../../database/migrations_squashed/2026_11_20_000003_repair_publication_history_constraints.php';
        $repair->up();
        $repair->up();
        $repair->down();
        self::assertNotNull($publication->fresh());
        $table = $publication->getTable();
        $foreignKeys = Schema::getForeignKeys($table);
        foreach ($foreignKeys as $foreignKey) {
            if (in_array('channel_connection_id', $foreignKey['columns'], true)) {
                self::assertSame('restrict', strtolower($foreignKey['on_delete']));

                return;
            }
        }
        self::fail('Publication connection foreign key was lost during upgrade.');
    }

    public function test_claim_rejects_a_changed_schedule_even_when_both_times_are_due(): void
    {
        $publication = $this->duePublication();
        Publication::query()->whereKey($publication->id)->update(['scheduled_at' => now()->subHours(2)]);
        self::assertFalse($publication->claimForPublishing());
    }

    public function test_service_preserves_the_job_schedule_snapshot_across_refresh(): void
    {
        $publication = $this->duePublication();
        $expected = $publication->scheduled_at->getTimestamp();
        Publication::query()->whereKey($publication->id)->update(['scheduled_at' => now()->subHours(2)]);
        $result = app(OmnichannelContext::class)->runAsSystem(
            fn () => app(PublishingService::class)->publish($publication, expectedScheduledAt: $expected),
        );
        self::assertSame('publication.schedule_changed', $result->errorCode);
        self::assertSame(0, $publication->fresh()->attempts);
    }

    public function test_dispatch_failure_releases_reservation_for_recovery(): void
    {
        Cache::flush();
        $publication = $this->duePublication();
        $key = 'omnichannel:scheduled-dispatch:'.$publication->id.':'.$publication->scheduled_at->getTimestamp();
        Bus::shouldReceive('dispatch')->once()->andThrow(new \RuntimeException('queue unavailable'));
        try {
            $this->artisan('omnichannel:dispatch-due')->run();
            self::fail('Queue failure must propagate.');
        } catch (\RuntimeException $exception) {
            self::assertSame('queue unavailable', $exception->getMessage());
        }
        self::assertFalse(Cache::has($key));
    }

    public function test_stuck_inspection_is_read_only_and_validates_limits(): void
    {
        $publication = $this->duePublication();
        self::assertTrue($publication->claimForPublishing());
        Publication::query()->whereKey($publication->id)->update(['last_attempted_at' => now()->subHours(2)]);
        $before = $publication->fresh()->getRawOriginal();
        $this->artisan('omnichannel:inspect-stuck', ['--channel' => $publication->channel_id])
            ->expectsOutputToContain('publishing')->assertExitCode(0);
        self::assertSame($before, $publication->fresh()->getRawOriginal());
        $this->artisan('omnichannel:inspect-stuck', ['--limit' => 1001])->assertExitCode(2);
    }

    private function duePublication(): Publication
    {
        $channel = Channel::query()->create([
            'name' => 'Demo', 'slug' => 'demo-'.Str::random(8), 'driver' => 'demo',
        ]);
        $connection = ChannelConnection::query()->create([
            'branch_id' => null, 'channel_id' => $channel->id, 'name' => 'Dispatch test',
            'credentials' => [], 'status' => 'active',
        ]);

        return Publication::factory()->due()->create([
            'branch_id' => null,
            'channel_id' => $channel->id,
            'channel_connection_id' => $connection->id,
        ]);
    }
}

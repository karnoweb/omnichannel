<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Tests\Feature;

use Carbon\CarbonImmutable;
use DateTimeImmutable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Karnoweb\Omnichannel\Http\ApiResponse;
use Karnoweb\Omnichannel\Jobs\PublishPublicationJob;
use Karnoweb\Omnichannel\Models\Channel;
use Karnoweb\Omnichannel\Models\ChannelConnection;
use Karnoweb\Omnichannel\Models\Publication;
use Karnoweb\Omnichannel\Tests\TestCase;
use ReflectionMethod;

final class QueueRetryPolicyTest extends TestCase
{

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations_squashed');
    }

    public function test_retry_after_accepts_numeric_header_and_bot_api_body_value(): void
    {
        Http::fakeSequence()
            ->push(['parameters' => ['retry_after' => 23]], 429)
            ->push([], 429, ['Retry-After' => '37'])
            ->push([], 429, ['Retry-After' => (new DateTimeImmutable('+90 seconds'))->format(DATE_RFC7231)]);

        $bodyResponse = ApiResponse::fromResponse(Http::get('https://api.test/body'));
        $headerResponse = ApiResponse::fromResponse(Http::get('https://api.test/header'));
        $dateResponse = ApiResponse::fromResponse(Http::get('https://api.test/date'));

        self::assertSame(23, $bodyResponse->retryAfterSeconds());
        self::assertSame(37, $headerResponse->retryAfterSeconds());
        self::assertGreaterThan(0, $dateResponse->retryAfterSeconds());
        self::assertLessThanOrEqual(90, $dateResponse->retryAfterSeconds());
    }

    public function test_job_retry_deadline_is_fixed_when_job_is_created(): void
    {
        CarbonImmutable::setTestNow('2026-11-20 12:00:00 UTC');
        config(['omnichannel.queue.retry_until_minutes' => 120]);
        $channel = Channel::query()->create(['name' => 'Demo', 'slug' => 'demo-'.Str::random(8), 'driver' => 'demo']);
        $connection = ChannelConnection::query()->create([
            'branch_id' => 12, 'channel_id' => $channel->id, 'name' => 'Queue test', 'credentials' => [], 'status' => 'active',
        ]);
        $publication = Publication::factory()->create([
            'branch_id' => 12, 'channel_id' => $channel->id, 'channel_connection_id' => $connection->id,
        ]);
        $job = new PublishPublicationJob($publication);
        $job = unserialize(serialize($job));
        $deadline = $job->retryUntil()->getTimestamp();

        CarbonImmutable::setTestNow('2026-11-20 12:45:00 UTC');

        self::assertSame($deadline, $job->retryUntil()->getTimestamp());
        self::assertSame($deadline, (new PublishPublicationJob($publication->fresh()))->retryUntil()->getTimestamp());
        self::assertSame(120, ($deadline - CarbonImmutable::parse('2026-11-20 12:00:00 UTC')->timestamp) / 60);
    }

    public function test_empty_backoff_uses_safe_defaults_and_rate_limit_delay_uses_server_wait(): void
    {
        config([
            'omnichannel.queue.backoff' => [],
            'omnichannel.queue.backoff_jitter_seconds' => 0,
        ]);
        $job = new PublishPublicationJob(new Publication);

        self::assertSame([30, 120, 300, 900], $job->backoff());
        $method = new ReflectionMethod($job, 'rateLimitedDelay');
        self::assertSame(47, $method->invoke($job, 47));
        self::assertSame(300, $method->invoke($job, null));
    }
}

<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Str;
use Karnoweb\Omnichannel\Enums\PublicationStatus;
use Karnoweb\Omnichannel\Models\Channel;
use Karnoweb\Omnichannel\Models\ChannelConnection;
use Karnoweb\Omnichannel\Models\Publication;
use Karnoweb\Omnichannel\Results\ReconciliationResult;
use Karnoweb\Omnichannel\Services\PublicationRecoveryService;
use Karnoweb\Omnichannel\Support\OmnichannelContext;
use Karnoweb\Omnichannel\Tests\TestCase;

final class PublicationRecoveryServiceTest extends TestCase
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

    public function test_manual_failure_decision_marks_publication_failed(): void
    {
        $channel = Channel::query()->create(['name' => 'Demo', 'slug' => 'demo-'.Str::random(8), 'driver' => 'demo']);
        $connection = ChannelConnection::query()->create([
            'branch_id' => 1,
            'channel_id' => $channel->id,
            'name' => 'Demo connection',
            'credentials' => ['access_token' => 'demo-token'],
            'status' => 'active',
        ]);
        $publication = Publication::factory()->create([
            'branch_id' => 1,
            'channel_id' => $channel->id,
            'channel_connection_id' => $connection->id,
            'status' => PublicationStatus::Publishing,
        ]);

        $result = app(OmnichannelContext::class)->runAsSystem(fn () => app(PublicationRecoveryService::class)->recordManualDecision(
            $publication,
            ReconciliationResult::ConfirmedFailure,
            'Operator verified remote failure.',
        ));

        self::assertSame(ReconciliationResult::ConfirmedFailure, $result->outcome);
        self::assertSame(PublicationStatus::Failed, $publication->fresh()->status);
    }
}
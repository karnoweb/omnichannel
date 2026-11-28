<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Tests\Feature;

use Karnoweb\Omnichannel\Enums\LogOperation;
use Karnoweb\Omnichannel\Enums\LogStatus;
use Karnoweb\Omnichannel\Models\Channel;
use Karnoweb\Omnichannel\Models\ChannelConnection;
use Karnoweb\Omnichannel\Models\PublicationLog;
use Karnoweb\Omnichannel\Tests\TestCase;

final class RedactSensitiveHistoryCommandTest extends TestCase
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

    public function test_dry_run_reports_counts_without_mutating_history_and_apply_redacts_it(): void
    {
        $secret = 'history-secret-value-12345';
        $channel = Channel::query()->create(['name' => 'Demo', 'slug' => 'demo', 'driver' => 'demo']);
        $connection = ChannelConnection::query()->create([
            'channel_id' => $channel->id,
            'name' => 'Connection',
            'credentials' => ['access_token' => $secret],
            'status' => 'active',
        ]);
        $log = PublicationLog::query()->create([
            'channel_connection_id' => $connection->id,
            'operation' => LogOperation::Publish,
            'status' => LogStatus::Failed,
            'attempt' => 1,
            'error_message' => 'Failed with '.$secret,
            'request' => ['message' => 'Failed with '.$secret],
            'response' => ['token' => $secret],
            'context' => [],
        ]);

        $this->artisan('omnichannel:redact-history')->assertExitCode(0)->expectsOutputToContain('Dry run only');
        self::assertSame('Failed with '.$secret, $log->fresh()->error_message);

        $this->artisan('omnichannel:redact-history', ['--apply' => true])->assertExitCode(0);
        $safe = $log->fresh()->toJson();
        self::assertStringNotContainsString($secret, $safe);
    }
}

<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Tests\Feature;

use Karnoweb\Omnichannel\Enums\FailureClass;
use Karnoweb\Omnichannel\Enums\LogOperation;
use Karnoweb\Omnichannel\Models\Channel;
use Karnoweb\Omnichannel\Models\ChannelConnection;
use Karnoweb\Omnichannel\Models\Publication;
use Karnoweb\Omnichannel\Results\PublishResult;
use Karnoweb\Omnichannel\Services\PublicationLogger;
use Karnoweb\Omnichannel\Tests\TestCase;

final class PublicationLoggerSecurityTest extends TestCase
{

    protected function defineEnvironment($app): void
    {
        $app['config']->set('omnichannel.general.prefix', 'omc_');
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../../database/migrations_squashed');
    }

    public function test_connection_secrets_never_appear_in_log_fields_or_model_json(): void
    {
        $token = 'audit-placeholder-token-value-112233';
        $channel = Channel::query()->create(['name' => 'Demo', 'slug' => 'demo', 'driver' => 'demo']);
        $connection = ChannelConnection::query()->create([
            'channel_id' => $channel->id,
            'name' => 'Audit connection',
            'credentials' => ['bot_token' => $token],
            'status' => 'active',
        ]);
        $publication = Publication::factory()->create(['channel_id' => $channel->id, 'channel_connection_id' => $connection->id]);
        $logger = $this->app->make(PublicationLogger::class);

        $log = $logger->start(LogOperation::Publish, $publication, $connection, ['url' => 'https://api.telegram.org/bot'.$token.'/sendMessage']);
        $logger->fail($log, FailureClass::Transient, 'network', 'Connection failed: '.$token, ['access_token' => $token]);

        $connection->forceFill(['status_message' => $token])->save();
        self::assertStringNotContainsString($token, $connection->fresh()->toArray()['status_message']);
        $stored = $log->fresh()->toJson().$connection->fresh()->toJson();
        self::assertStringNotContainsString($token, $stored);
        $result = PublishResult::failure(FailureClass::Transient, 'Failed with '.$token, metadata: ['token' => $token]);
        self::assertStringNotContainsString($token, json_encode($result->toArray()) ?: '');
        self::assertStringNotContainsString($token, $publication->fresh()->toJson());
    }
}

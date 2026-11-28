<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Karnoweb\Omnichannel\Models\Channel;
use Karnoweb\Omnichannel\Models\ChannelConnection;
use Karnoweb\Omnichannel\Services\ConnectionTokenRefreshLock;
use Karnoweb\Omnichannel\Tests\TestCase;

final class TokenRefreshConcurrencyTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $app['config']->set('omnichannel.queue.token_refresh_lock_wait_seconds', 0);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations_squashed');
    }

    public function test_stale_refresh_worker_uses_tokens_written_by_the_lock_owner(): void
    {
        $connection = $this->connection();
        $staleWorker = $connection->fresh();
        $connection->mergeCredentials(['access_token' => 'new-access', 'refresh_token' => 'new-refresh']);
        $connection->forceFill(['expires_at' => now()->addDays(30)])->save();
        $called = false;

        $result = app(ConnectionTokenRefreshLock::class)->run($staleWorker, function () use (&$called): bool {
            $called = true;

            return false;
        });

        self::assertTrue($result);
        self::assertFalse($called);
        self::assertSame('new-refresh', $staleWorker->credential('refresh_token'));
    }

    public function test_a_contended_refresh_lock_does_not_start_a_second_refresh(): void
    {
        $connection = $this->connection();
        $lock = Cache::lock('omnichannel:token-refresh:'.$connection->id, 180);
        self::assertTrue($lock->get());
        $called = false;

        try {
            $result = app(ConnectionTokenRefreshLock::class)->run($connection->fresh(), function () use (&$called): bool {
                $called = true;

                return true;
            });
        } finally {
            $lock->release();
        }

        self::assertFalse($result);
        self::assertFalse($called);
    }

    private function connection(): ChannelConnection
    {
        $channel = Channel::query()->create([
            'name' => 'Instagram', 'slug' => 'instagram-'.Str::random(8), 'driver' => 'instagram',
        ]);

        return ChannelConnection::query()->create([
            'branch_id' => null,
            'channel_id' => $channel->id,
            'name' => 'Token refresh test',
            'credentials' => ['access_token' => 'old-access', 'refresh_token' => 'old-refresh'],
            'expires_at' => now()->addDay(),
            'status' => 'active',
        ]);
    }
}

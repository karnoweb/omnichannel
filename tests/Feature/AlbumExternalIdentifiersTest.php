<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Karnoweb\Omnichannel\Channels\Telegram\TelegramDriver;
use Karnoweb\Omnichannel\Enums\PublicationType;
use Karnoweb\Omnichannel\Models\Channel;
use Karnoweb\Omnichannel\Models\ChannelConnection;
use Karnoweb\Omnichannel\Models\Publication;
use Karnoweb\Omnichannel\Models\PublicationLog;
use Karnoweb\Omnichannel\Tests\TestCase;
use Karnoweb\Omnichannel\ValueObjects\PublicationContent;

final class AlbumExternalIdentifiersTest extends TestCase
{
    use DatabaseMigrations;

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $app['config']->set('omnichannel.channels.telegram.base_url', 'https://telegram.test');
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations_squashed');
    }

    public function test_album_publish_persists_all_ids_and_replay_keeps_them(): void
    {
        [$publication] = $this->album();
        Http::fake(fn () => Http::response(['ok' => true, 'result' => [
            ['message_id' => 11], ['message_id' => 12], ['message_id' => 13],
        ]], 200));

        $driver = app(TelegramDriver::class);
        $result = $driver->publish($publication);

        self::assertTrue($result->successful);
        self::assertSame('11', $result->externalId);
        self::assertSame(['11', '12', '13'], $publication->persistentArtifact('publish', 'external_ids'));
        self::assertSame(['11', '12', '13'], $result->metadata['external_ids']);
    }

    public function test_partial_album_delete_records_remaining_ids_and_retry_targets_only_them(): void
    {
        [$publication] = $this->album('11');
        $publication->rememberPersistentArtifact('publish', 'external_ids', ['11', '12', '13']);
        $deleteCalls = [];
        Http::fake(function ($request) use (&$deleteCalls) {
            $body = $request->data();
            $deleteCalls[] = (string) ($body['message_id'] ?? '');

            if (count($deleteCalls) === 2) {
                return Http::response(['ok' => false, 'error_code' => 400, 'description' => 'message cannot be deleted'], 400);
            }

            return Http::response(['ok' => true, 'result' => true], 200);
        });

        $driver = app(TelegramDriver::class);
        $partial = $driver->delete($publication);

        self::assertFalse($partial->successful);
        self::assertSame(['12', '13'], $partial->metadata['remaining_message_ids']);
        self::assertSame(['12', '13'], $publication->persistentArtifact('delete', 'remaining_external_ids'));

        $retry = $driver->delete($publication->fresh());

        self::assertTrue($retry->successful);
        self::assertSame(['11', '12', '12', '13'], $deleteCalls);
        self::assertNull($publication->fresh()->persistentArtifact('delete', 'remaining_external_ids'));
    }

    public function test_external_delete_keeps_publication_and_history_rows(): void
    {
        [$publication, $connection] = $this->album('11');
        $log = PublicationLog::query()->create([
            'publication_id' => $publication->id,
            'channel_connection_id' => $connection->id,
            'operation' => 'delete',
            'status' => 'succeeded',
            'attempt' => 1,
            'response' => ['deleted_message_ids' => ['11']],
        ]);
        Http::fake(fn () => Http::response(['ok' => true, 'result' => true], 200));
        $result = app(TelegramDriver::class)->delete($publication);

        self::assertTrue($result->successful);
        self::assertTrue(Publication::query()->whereKey($publication->id)->exists());
        self::assertTrue(PublicationLog::query()->whereKey($log->id)->exists());
    }

    /** @return array{Publication, ChannelConnection} */
    private function album(?string $externalId = null): array
    {
        $channel = Channel::query()->create([
            'name' => 'Telegram', 'slug' => 'telegram-'.Str::random(8), 'driver' => 'telegram',
        ]);
        $connection = ChannelConnection::query()->create([
            'branch_id' => null,
            'channel_id' => $channel->id,
            'name' => 'Test connection',
            'credentials' => ['bot_token' => 'fake-token', 'chat_id' => '-100123'],
            'status' => 'active',
        ]);
        $publication = Publication::factory()->create([
            'channel_id' => $channel->id,
            'channel_connection_id' => $connection->id,
            'type' => PublicationType::Album,
            'external_id' => $externalId,
            'content' => PublicationContent::fromArray([
                'caption' => 'Album caption',
                'media' => [
                    ['type' => 'image', 'url' => 'https://example.test/one.jpg'],
                    ['type' => 'image', 'url' => 'https://example.test/two.jpg'],
                    ['type' => 'image', 'url' => 'https://example.test/three.jpg'],
                ],
                'hashtags' => [],
                'extra' => [],
            ])->toArray(),
        ]);

        return [$publication, $connection];
    }
}

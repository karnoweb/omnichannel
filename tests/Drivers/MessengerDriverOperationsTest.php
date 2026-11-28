<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Tests\Drivers;

use Illuminate\Support\Facades\Http;
use Karnoweb\Omnichannel\Enums\PublicationType;
use Karnoweb\Omnichannel\Models\Channel;
use Karnoweb\Omnichannel\Models\ChannelConnection;
use Karnoweb\Omnichannel\Models\Publication;
use Karnoweb\Omnichannel\Services\ChannelManager;
use Karnoweb\Omnichannel\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class MessengerDriverOperationsTest extends TestCase
{

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations_squashed');
    }

    #[DataProvider('messengers')]
    public function test_text_operations_follow_each_driver_contract(
        string $key,
        string $prefix,
        array $response,
        ?string $check,
        string $textField,
        ?string $edit,
    ): void {
        config(["omnichannel.channels.$key.base_url" => 'https://provider.test']);
        $channel = Channel::factory()->create(['driver' => $key]);
        $connection = ChannelConnection::factory()->create([
            'channel_id' => $channel->id,
            'credentials' => ['access_token' => 'sample-token', 'chat_id' => 'sample-chat'],
        ]);
        $publication = Publication::factory()->onChannel($channel, $connection)->create([
            'type' => PublicationType::Message,
            'content' => ['caption' => 'Original'],
        ]);
        $url = 'https://provider.test'.$prefix;
        Http::preventStrayRequests();
        $responses = [$url.'/sendMessage' => Http::response($response)];
        if ($check !== null) {
            $responses[$url.'/'.$check] = Http::response($response);
            $responses[$url.'/'.$edit] = Http::response($response);
            $responses[$url.'/deleteMessage'] = Http::response($response);
        }
        Http::fake($responses);

        $driver = app(ChannelManager::class)->driver($key);
        self::assertTrue($driver->testConnection($connection)->successful);
        self::assertTrue($driver->validate($publication)->passes());
        $published = $driver->publish($publication);
        self::assertTrue($published->successful, $published->errorMessage ?? '');
        self::assertSame('message-42', $published->externalId);

        $publication->forceFill(['external_id' => $published->externalId, 'content' => ['caption' => 'Edited']])->save();
        self::assertSame($edit !== null, $driver->update($publication)->successful);
        self::assertSame($edit !== null, $driver->delete($publication)->successful);

        Http::assertSent(fn ($request): bool => $request->method() === 'POST'
            && $request->url() === $url.'/sendMessage'
            && $request['chat_id'] === 'sample-chat'
            && $request[$textField] === 'Original');
        if ($edit !== null) {
            Http::assertSent(fn ($request): bool => $request->method() === 'POST'
                && $request->url() === $url.'/'.$edit
                && $request['message_id'] === 'message-42'
                && $request[$textField] === 'Edited');
            Http::assertSent(fn ($request): bool => $request->method() === 'POST'
                && $request->url() === $url.'/deleteMessage'
                && $request['message_id'] === 'message-42');
            Http::assertSent(fn ($request): bool => $request->method() === 'POST'
                && $request->url() === $url.'/'.$check);
        }
        Http::assertSentCount($edit === null ? 1 : 4);
    }

    public static function messengers(): iterable
    {
        $botResponse = ['ok' => true, 'result' => ['message_id' => 'message-42']];
        yield 'Telegram' => ['telegram', '/botsample-token', $botResponse, 'getMe', 'text', 'editMessageText'];
        yield 'Bale' => ['bale', '/botsample-token', $botResponse, 'getMe', 'text', 'editMessageText'];
        yield 'Rubika' => ['rubika', '/sample-token', ['status' => 'OK', 'data' => ['message_id' => 'message-42']], 'getMe', 'text', 'editMessageText'];
        yield 'Gap' => ['gap', '', ['id' => 'message-42'], 'sendAction', 'data', 'editMessage'];
        yield 'Eitaa' => ['eitaa', '/api/sample-token', ['ok' => true, 'result' => ['message_id' => 'message-42']], null, 'text', null];
    }
}

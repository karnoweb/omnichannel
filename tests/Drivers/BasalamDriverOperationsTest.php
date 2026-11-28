<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Tests\Drivers;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Http;
use Karnoweb\Omnichannel\Channels\Basalam\BasalamDriver;
use Karnoweb\Omnichannel\Contracts\MediaBytesResolverContract;
use Karnoweb\Omnichannel\Enums\FailureClass;
use Karnoweb\Omnichannel\Enums\PublicationType;
use Karnoweb\Omnichannel\Models\Channel;
use Karnoweb\Omnichannel\Models\ChannelConnection;
use Karnoweb\Omnichannel\Models\Publication;
use Karnoweb\Omnichannel\Tests\TestCase;

final class BasalamDriverOperationsTest extends TestCase
{
    use DatabaseMigrations;

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $app['config']->set('omnichannel.channels.basalam.base_url', 'https://basalam.test');
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations_squashed');
    }

    public function test_listing_operations_use_the_vendor_and_product_identifiers(): void
    {
        $channel = Channel::factory()->create(['driver' => 'basalam']);
        $connection = ChannelConnection::factory()->create([
            'channel_id' => $channel->id,
            'credentials' => ['access_token' => 'sample-token'],
            'metadata' => ['vendor_id' => 42],
        ]);
        $publication = Publication::factory()->onChannel($channel, $connection)->create([
            'type' => PublicationType::Product,
            'content' => [
                'title' => 'Example listing',
                'caption' => 'Example product description',
                'media' => [['type' => 'image', 'url' => 'https://assets.test/product.jpg']],
                'extra' => ['price' => 1200, 'basalam' => ['category_id' => 17]],
            ],
        ]);
        $this->mock(MediaBytesResolverContract::class)
            ->shouldReceive('fetch')->once()->with('https://assets.test/product.jpg')->andReturn('synthetic photo');
        Http::preventStrayRequests();
        Http::fake([
            'https://basalam.test/v1/users/me' => Http::response(['vendor' => ['id' => 42]]),
            'https://basalam.test/v1/files' => Http::response(['id' => 21]),
            'https://basalam.test/v1/vendors/42/products' => Http::response(['id' => 91]),
            'https://basalam.test/v1/products/91' => Http::response(['id' => 91]),
        ]);

        $driver = app(BasalamDriver::class);
        self::assertSame('42', $driver->testConnection($connection)->accountId);
        self::assertTrue($driver->validate($publication)->passes());
        $published = $driver->publish($publication);
        self::assertTrue($published->successful, $published->errorMessage ?? '');
        self::assertSame('91', $published->externalId);
        self::assertSame('91', $driver->publish($publication->fresh())->externalId);

        $publication->forceFill(['external_id' => '91'])->save();
        self::assertTrue($driver->update($publication)->successful);
        self::assertTrue($driver->deactivate($publication)->successful);
        self::assertFalse($driver->delete($publication)->successful);

        Http::assertSentCount(5);
        $requests = Http::recorded()->map(fn (array $pair) => $pair[0])->all();
        self::assertSame([
            ['GET', '/v1/users/me'],
            ['POST', '/v1/files'],
            ['POST', '/v1/vendors/42/products'],
            ['PATCH', '/v1/products/91'],
            ['PATCH', '/v1/products/91'],
        ], array_map(fn ($request) => [$request->method(), parse_url($request->url(), PHP_URL_PATH)], $requests));
        self::assertStringContainsString('synthetic photo', $requests[1]->body());
        self::assertSame(17, $requests[2]['category_id']);
        self::assertSame([21], $requests[2]['photos']);
        self::assertSame(1200, $requests[3]['primary_price']);
        self::assertSame(['status' => config('omnichannel.channels.basalam.product_status.draft')], $requests[4]->data());
    }

    public function test_connection_without_a_vendor_reports_a_permission_failure(): void
    {
        $connection = new ChannelConnection(['credentials' => ['access_token' => 'sample-token']]);
        Http::preventStrayRequests();
        Http::fake(['https://basalam.test/v1/users/me' => Http::response(['id' => 42])]);

        $result = app(BasalamDriver::class)->testConnection($connection);

        self::assertSame(FailureClass::Permission, $result->failureClass);
        self::assertSame('basalam.auth.no_vendor', $result->errorCode);
    }
}

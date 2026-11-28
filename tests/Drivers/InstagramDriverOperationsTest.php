<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Tests\Drivers;

use Illuminate\Support\Facades\Http;
use Karnoweb\Omnichannel\Channels\Instagram\InstagramDriver;
use Karnoweb\Omnichannel\Enums\FailureClass;
use Karnoweb\Omnichannel\Enums\PublicationStatus;
use Karnoweb\Omnichannel\Enums\PublicationType;
use Karnoweb\Omnichannel\Models\Channel;
use Karnoweb\Omnichannel\Models\ChannelConnection;
use Karnoweb\Omnichannel\Models\Publication;
use Karnoweb\Omnichannel\Services\PublishingService;
use Karnoweb\Omnichannel\Support\OmnichannelContext;
use Karnoweb\Omnichannel\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class InstagramDriverOperationsTest extends TestCase
{
    private const API = 'https://instagram.test/v26.0';

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $app['config']->set('omnichannel.channels.instagram.graph_url', 'https://instagram.test');
        $app['config']->set('omnichannel.channels.instagram.container_poll', ['attempts' => 2, 'interval_seconds' => 0]);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations_squashed');
    }

    #[DataProvider('publicationTypes')]
    public function test_publication_uses_the_expected_container_payload(
        PublicationType $type,
        array $media,
        array $expectedPayload,
    ): void {
        $publication = $this->publication($type, $media);
        $driver = app(InstagramDriver::class);
        $containers = Http::sequence();
        $carousel = $type === PublicationType::Carousel;
        if ($carousel) {
            $containers->push(['id' => 'child-1'])->push(['id' => 'child-2']);
        }
        $containers->push(['id' => 'container']);
        Http::preventStrayRequests();
        Http::fake([
            self::API.'/me?*' => Http::response(['user_id' => 'account', 'username' => 'sample']),
            self::API.'/account/media' => $containers,
            self::API.'/child-1?*' => Http::response(['status_code' => 'FINISHED']),
            self::API.'/child-2?*' => Http::response(['status_code' => 'FINISHED']),
            self::API.'/container?*' => Http::response(['status_code' => 'FINISHED']),
            self::API.'/account/media_publish' => Http::response(['id' => 'published']),
            self::API.'/published' => Http::response(['success' => true]),
            self::API.'/published?*' => Http::response(['permalink' => 'https://example.test/published']),
        ]);

        self::assertSame('account', $driver->testConnection($publication->connection)->accountId);
        self::assertTrue($driver->validate($publication)->passes());
        $result = $driver->publish($publication);
        self::assertTrue($result->successful, $result->errorMessage ?? '');
        self::assertSame('published', $result->externalId);
        self::assertSame('published', $driver->publish($publication->fresh())->externalId);

        $publication->forceFill(['external_id' => 'published']);
        $editable = $type !== PublicationType::Story;
        self::assertSame($editable, $driver->update($publication)->successful);
        self::assertFalse($driver->delete($publication)->successful);

        $creations = Http::recorded(fn ($request) => $request->method() === 'POST'
            && $request->url() === self::API.'/account/media');
        self::assertCount($carousel ? 3 : 1, $creations);
        $parent = $creations->last()[0];
        foreach ($expectedPayload as $field => $value) {
            self::assertSame($value, $parent[$field], $field);
        }
        if ($carousel) {
            foreach ($creations->take(2)->values() as $index => [$child]) {
                self::assertSame($media[$index]['url'], $child['image_url']);
                self::assertTrue($child['is_carousel_item']);
            }
        }
        Http::assertSent(fn ($request) => $request->method() === 'POST'
            && $request->url() === self::API.'/account/media_publish'
            && $request['creation_id'] === 'container');
        if ($editable) {
            Http::assertSent(fn ($request) => $request->method() === 'POST'
                && $request->url() === self::API.'/published'
                && $request['caption'] === 'Example caption');
        }
        Http::assertSentCount(5 + (int) $editable + ($carousel ? 4 : 0));
    }

    public function test_pending_container_stops_at_the_poll_limit_and_resumes_without_recreating_it(): void
    {
        $publication = $this->publication(PublicationType::Post, [['type' => 'image', 'url' => 'https://assets.test/photo.jpg']]);
        Http::preventStrayRequests();
        Http::fake([
            self::API.'/account/media' => Http::sequence()->push(['id' => 'container']),
            self::API.'/container?*' => Http::sequence()
                ->push(['status_code' => 'IN_PROGRESS'])->push(['status_code' => 'IN_PROGRESS'])
                ->push(['status_code' => 'FINISHED']),
            self::API.'/account/media_publish' => Http::sequence()->push(['error' => ['message' => 'Timed out']], 408),
        ]);

        $service = app(PublishingService::class);
        $context = app(OmnichannelContext::class);
        $pending = $context->runForBranch($publication->branch_id, fn () => $service->publish($publication));
        self::assertSame(FailureClass::Transient, $pending->failureClass);
        self::assertSame('instagram.container.still_processing', $pending->errorCode);
        Http::assertSentCount(3);

        $resumed = $context->runForBranch($publication->branch_id, fn () => $service->publish($publication->fresh()));
        self::assertSame(FailureClass::UnknownOutcome, $resumed->failureClass);
        self::assertSame(PublicationStatus::UnknownOutcome, $publication->fresh()->status);

        $replayed = $context->runForBranch($publication->branch_id, fn () => $service->publish($publication->fresh()));
        self::assertFalse($replayed->successful);
        Http::assertSentCount(5);
        self::assertCount(1, Http::recorded(fn ($request) => $request->url() === self::API.'/account/media_publish'));
    }

    public static function publicationTypes(): iterable
    {
        $image = ['type' => 'image', 'url' => 'https://assets.test/photo.jpg'];
        $video = ['type' => 'video', 'url' => 'https://assets.test/clip.mp4'];
        yield 'post' => [PublicationType::Post, [$image], ['image_url' => $image['url']]];
        yield 'reel' => [PublicationType::Reel, [$video], ['media_type' => 'REELS', 'video_url' => $video['url']]];
        yield 'story' => [PublicationType::Story, [$image], ['media_type' => 'STORIES', 'image_url' => $image['url']]];
        yield 'carousel' => [PublicationType::Carousel, [$image, ['type' => 'image', 'url' => 'https://assets.test/second.jpg']], [
            'media_type' => 'CAROUSEL', 'children' => 'child-1,child-2',
        ]];
    }

    private function publication(PublicationType $type, array $media): Publication
    {
        $channel = Channel::factory()->create(['driver' => 'instagram']);
        $connection = ChannelConnection::factory()->create([
            'channel_id' => $channel->id,
            'branch_id' => 42,
            'external_account_id' => 'account',
            'credentials' => ['access_token' => 'sample-token'],
        ]);

        return Publication::factory()->onChannel($channel, $connection)->create([
            'type' => $type,
            'content' => ['caption' => 'Example caption', 'media' => $media],
        ]);
    }
}

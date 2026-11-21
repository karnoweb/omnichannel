<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Karnoweb\Omnichannel\Channels\WhatsApp\WhatsAppCatalogClient;
use Karnoweb\Omnichannel\Channels\WhatsApp\WhatsAppDriver;
use Karnoweb\Omnichannel\Enums\FailureClass;
use Karnoweb\Omnichannel\Enums\PublicationStatus;
use Karnoweb\Omnichannel\Enums\PublicationType;
use Karnoweb\Omnichannel\Models\Channel;
use Karnoweb\Omnichannel\Models\ChannelConnection;
use Karnoweb\Omnichannel\Models\Publication;
use Karnoweb\Omnichannel\Tests\TestCase;

final class WhatsAppCatalogBatchOutcomeTest extends TestCase
{
    use DatabaseMigrations;

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:' . base64_encode(str_repeat('a', 32)));
        $app['config']->set('omnichannel.channels.whatsapp.graph_url', 'https://graph.facebook.test');
        $app['config']->set('omnichannel.channels.whatsapp.api_version', 'v99.0');
        $app['config']->set('omnichannel.channels.whatsapp.product_link_base', 'https://shop.test/products');
        $app['config']->set('omnichannel.channels.whatsapp.default_brand', 'Test Brand');
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations_squashed');
    }

    public function test_successful_http_batch_response_is_persisted_as_unknown_and_replay_is_blocked(): void
    {
        $publication = $this->publication();
        Http::fake(fn () => Http::response(['handles' => ['batch-123']], 200));
        $driver = app(WhatsAppDriver::class);

        $result = $driver->publish($publication);
        $pending = $publication->fresh()->persistentArtifact('create', 'pending_batch');

        self::assertFalse($result->successful);
        self::assertSame(FailureClass::UnknownOutcome, $result->failureClass);
        self::assertSame(['batch-123'], $pending['handles']);
        self::assertNull($publication->fresh()->persistentArtifact('publish', 'external_id'));

        $replay = $driver->publish($publication->fresh());
        self::assertSame(FailureClass::UnknownOutcome, $replay->failureClass);
        Http::assertSentCount(1);
    }

    public function test_delete_http_success_does_not_claim_that_catalog_item_was_deleted(): void
    {
        $publication = $this->publication();
        $publication->forceFill(['external_id' => 'catalog-item-3'])->save();
        Http::fake(fn () => Http::response(['handle' => 'delete-123'], 200));
        $driver = app(WhatsAppDriver::class);

        $result = $driver->delete($publication);

        self::assertFalse($result->successful);
        self::assertSame(FailureClass::UnknownOutcome, $result->failureClass);
        self::assertSame(['delete-123'], $publication->fresh()->persistentArtifact('delete', 'pending_batch')['handles']);
    }

    public function test_pending_create_batch_blocks_delete_before_a_second_external_request(): void
    {
        $publication = $this->publication();
        Http::fake(fn () => Http::response(['handle' => 'create-123'], 200));
        $driver = app(WhatsAppDriver::class);

        $publish = $driver->publish($publication);
        $delete = $driver->delete($publication->fresh());

        self::assertSame(FailureClass::UnknownOutcome, $publish->failureClass);
        self::assertSame(FailureClass::UnknownOutcome, $delete->failureClass);
        self::assertSame('whatsapp.batch.pending', $delete->errorCode);
        Http::assertSentCount(1);
    }

    public function test_batch_status_lookup_uses_catalog_handle_and_safe_get(): void
    {
        Http::fake(fn () => Http::response(['data' => [[
            'handle' => 'batch-123', 'status' => 'finished', 'errors_total_count' => 0,
        ]]], 200));

        $response = app(WhatsAppCatalogClient::class)->checkBatchRequestStatus('catalog-12', 'batch-123', 'test-token');

        self::assertFalse($response->failed());
        self::assertSame('finished', $response->get('data.0.status'));
        Http::assertSent(fn ($request): bool => $request->method() === 'GET'
            && str_ends_with($request->url(), '/catalog-12/check_batch_request_status?handle=batch-123'));
    }

    public function test_inspection_command_persists_status_without_finalizing_publication(): void
    {
        $publication = $this->publication();
        $publication->rememberPersistentArtifact('create', 'pending_batch', [
            'handles' => ['batch-123'], 'status' => 'pending',
        ]);
        Http::fake(fn () => Http::response(['data' => [[
            'handle' => 'batch-123', 'status' => 'finished', 'errors_total_count' => 1,
            'errors' => [['message' => 'Invalid field with test-token']],
        ]]], 200));

        self::assertSame(0, Artisan::call('omnichannel:inspect-whatsapp-batch', ['publication' => $publication->id]));
        $output = Artisan::output();
        self::assertStringContainsString('batch-123', $output, $output);
        self::assertStringContainsString('finished', $output, $output);
        self::assertStringContainsString('errors=1', $output, $output);
        self::assertStringNotContainsString('test-token', $output);

        $fresh = $publication->fresh();
        self::assertSame(PublicationStatus::Draft, $fresh->status);
        $savedStatus = $fresh->persistentArtifact('create', 'pending_batch')['status_checks'][0];
        self::assertSame(1, $savedStatus['errors_total_count']);
        self::assertStringNotContainsString('test-token', $savedStatus['errors'][0]['message']);
    }

    private function publication(): Publication
    {
        $channel = Channel::query()->create([
            'name' => 'WhatsApp', 'slug' => 'whatsapp-' . Str::random(8), 'driver' => 'whatsapp',
        ]);
        $connection = ChannelConnection::query()->create([
            'branch_id' => 12,
            'channel_id' => $channel->id,
            'name' => 'WhatsApp test connection',
            'credentials' => ['access_token' => 'test-token', 'catalog_id' => 'catalog-12'],
            'status' => 'active',
        ]);

        return Publication::factory()->create([
            'branch_id' => 12,
            'product_id' => 'product-12',
            'product_name' => 'Product 12',
            'channel_id' => $channel->id,
            'channel_connection_id' => $connection->id,
            'type' => PublicationType::Product,
            'content' => [
                'title' => 'Product 12', 'caption' => 'A useful product',
                'media' => [['type' => 'image', 'url' => 'https://cdn.shop.test/product.jpg']],
                'hashtags' => [], 'extra' => ['price' => '12.00'],
            ],
        ]);
    }
}

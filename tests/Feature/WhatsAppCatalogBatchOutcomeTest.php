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
use Karnoweb\Omnichannel\Results\ReconciliationResult;
use Karnoweb\Omnichannel\Services\WhatsAppCatalogReconciliationService;
use Karnoweb\Omnichannel\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class WhatsAppCatalogBatchOutcomeTest extends TestCase
{
    use DatabaseMigrations;

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $app['config']->set('omnichannel.channels.whatsapp.graph_url', 'https://graph.facebook.test');
        $app['config']->set('omnichannel.channels.whatsapp.api_version', 'v99.0');
        $app['config']->set('omnichannel.channels.whatsapp.product_link_base', 'https://shop.test/products');
        $app['config']->set('omnichannel.channels.whatsapp.default_brand', 'Test Brand');
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations_squashed');
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

    #[DataProvider('batchEvidence')]
    public function test_finalization_requires_evidence_for_every_handle(array $handles, array $row, int $code, string $expected): void
    {
        config(['omnichannel.whatsapp_catalog.auto_reconcile' => true]);
        $publication = $this->publication();
        $publication->rememberPersistentArtifact('create', 'pending_batch', ['handles' => $handles]);
        Http::preventStrayRequests();
        Http::fake(['https://graph.facebook.test/*' => fn ($request) => Http::response(['data' => [[
            'handle' => str_ends_with($request->url(), 'handle=first') ? 'first' : 'second',
            ...$row,
        ]]], $code)]);
        $service = app(WhatsAppCatalogReconciliationService::class);

        self::assertSame($expected, $service->finalizeWhenReady($publication)->outcome);
        self::assertSame($expected, $service->finalizeWhenReady($publication->fresh())->outcome);
        Http::assertNotSent(fn ($request): bool => $request->method() !== 'GET');
    }

    public static function batchEvidence(): iterable
    {
        $complete = ['status' => 'finished', 'errors_total_count' => 0];
        yield 'multiple handles' => [['first', 'second'], $complete, 200, ReconciliationResult::ConfirmedSuccess];
        yield 'no handle' => [[], $complete, 200, ReconciliationResult::Unknown];
        yield 'mixed malformed handle' => [['first', 7], $complete, 200, ReconciliationResult::Unknown];
        yield 'mismatched response handle' => [['first'], ['handle' => 'other', ...$complete], 200, ReconciliationResult::Unknown];
        yield 'missing error count' => [['first'], ['status' => 'completed'], 200, ReconciliationResult::Unknown];
        yield 'failed item' => [['first'], ['status' => 'finished', 'errors_total_count' => 1], 200, ReconciliationResult::ConfirmedFailure];
        yield 'contradictory item evidence' => [['first'], [...$complete, 'errors' => [['id' => 'item']]], 200, ReconciliationResult::Unknown];
        yield 'pending' => [['first'], ['status' => 'pending'], 200, ReconciliationResult::Pending];
        yield 'HTTP failure' => [['first'], $complete, 503, ReconciliationResult::Unknown];
    }

    public function test_missing_or_invalid_evidence_from_any_operation_blocks_finalization(): void
    {
        config(['omnichannel.whatsapp_catalog.auto_reconcile' => true]);
        $publication = $this->publication();
        $publication->rememberPersistentArtifact('create', 'pending_batch', ['handles' => ['first']]);
        $publication->rememberPersistentArtifact('update', 'pending_batch', ['handles' => []]);
        Http::preventStrayRequests();
        Http::fake(['https://graph.facebook.test/*' => Http::response(['data' => [[
            'handle' => 'first', 'status' => 'finished', 'errors_total_count' => 0,
        ]]])]);

        $result = app(WhatsAppCatalogReconciliationService::class)->finalizeWhenReady($publication);

        self::assertSame(ReconciliationResult::Unknown, $result->outcome);
        Http::assertSentCount(1);
    }

    public function test_ambiguous_batch_lookup_response_does_not_confirm_success(): void
    {
        config(['omnichannel.whatsapp_catalog.auto_reconcile' => true]);
        $publication = $this->publication();
        $publication->rememberPersistentArtifact('create', 'pending_batch', ['handles' => ['first']]);
        Http::preventStrayRequests();
        Http::fake(['https://graph.facebook.test/*' => Http::response(['data' => [
            ['handle' => 'first', 'status' => 'finished', 'errors_total_count' => 0],
            ['handle' => 'first', 'status' => 'finished', 'errors_total_count' => 1],
        ]])]);

        $result = app(WhatsAppCatalogReconciliationService::class)->finalizeWhenReady($publication);

        self::assertSame(ReconciliationResult::Unknown, $result->outcome);
    }

    public function test_transport_failure_after_acceptance_leaves_batch_outcome_unknown(): void
    {
        config(['omnichannel.whatsapp_catalog.auto_reconcile' => true]);
        $publication = $this->publication();
        $publication->rememberPersistentArtifact('create', 'pending_batch', ['handles' => ['first']]);
        Http::preventStrayRequests();
        Http::fake(['https://graph.facebook.test/*' => Http::failedConnection()]);

        $result = app(WhatsAppCatalogReconciliationService::class)->finalizeWhenReady($publication);

        self::assertSame(ReconciliationResult::Unknown, $result->outcome);
        self::assertSame(['first'], $publication->fresh()->persistentArtifact('create', 'pending_batch')['handles']);
    }

    #[DataProvider('catalogOperations')]
    public function test_catalog_operations_only_submit_a_pending_batch(string $operation, string $method): void
    {
        $publication = $this->publication();
        $driver = app(WhatsAppDriver::class);
        Http::preventStrayRequests();
        Http::fake([
            'https://graph.facebook.test/v99.0/catalog-12?*' => Http::response(['id' => 'catalog-12']),
            'https://graph.facebook.test/v99.0/catalog-12/items_batch' => Http::response(['handles' => ['first', 7]]),
        ]);

        self::assertSame('catalog-12', $driver->testConnection($publication->connection)->accountId);
        self::assertTrue($driver->validate($publication)->passes());
        self::assertSame(FailureClass::UnknownOutcome, $driver->{$operation}($publication)->failureClass);
        $artifact = $operation === 'publish' ? 'create' : $operation;
        self::assertSame(['first', null], $publication->fresh()->persistentArtifact($artifact, 'pending_batch')['handles']);
        Http::assertSent(function ($request) use ($method, $operation): bool {
            if ($request->method() !== 'POST' || ! str_ends_with($request->url(), '/catalog-12/items_batch')) {
                return false;
            }

            $item = json_decode($request['requests'], true)[0];

            return $request['item_type'] === 'PRODUCT_ITEM'
                && $item['method'] === $method
                && ($operation !== 'deactivate' || $item['data']['availability'] === 'out of stock');
        });
        Http::assertSentCount(2);
    }

    public static function catalogOperations(): iterable
    {
        yield ['publish', 'CREATE'];
        yield ['update', 'UPDATE'];
        yield ['delete', 'DELETE'];
        yield ['deactivate', 'UPDATE'];
    }

    private function publication(): Publication
    {
        $channel = Channel::query()->create([
            'name' => 'WhatsApp', 'slug' => 'whatsapp-'.Str::random(8), 'driver' => 'whatsapp',
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

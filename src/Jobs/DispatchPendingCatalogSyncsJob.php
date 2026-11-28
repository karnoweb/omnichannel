<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Karnoweb\Omnichannel\Enums\Capability;
use Karnoweb\Omnichannel\Enums\CatalogSyncStatus;
use Karnoweb\Omnichannel\Enums\ConnectionStatus;
use Karnoweb\Omnichannel\Enums\PublicationSourceType;
use Karnoweb\Omnichannel\Enums\PublicationStatus;
use Karnoweb\Omnichannel\Enums\PublicationType;
use Karnoweb\Omnichannel\Models\CatalogSyncState;
use Karnoweb\Omnichannel\Models\Publication;
use Karnoweb\Omnichannel\Services\ChannelManager;
use Karnoweb\Omnichannel\ValueObjects\CatalogListingIdentity;

final class DispatchPendingCatalogSyncsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly int $catalogSyncStateId) {}

    public function handle(ChannelManager $channels): void
    {
        if (! (bool) config('omnichannel.catalog_sync.enabled', false)) {
            return;
        }
        $state = CatalogSyncState::query()->find($this->catalogSyncStateId);
        if ($state === null || $state->status === CatalogSyncStatus::Synced) {
            return;
        }
        $publication = Publication::query()->with(['connection.channel'])->find($state->publication_id);
        if ($publication === null || $state->branch_id !== $publication->branch_id
            || $state->listing_identity !== (new CatalogListingIdentity(
                (int) $publication->channel_connection_id,
                (string) $publication->product_id,
                $publication->external_id,
            ))->key()
            || $publication->source_type !== PublicationSourceType::Product
            || $publication->type !== PublicationType::Product
            || $publication->status !== PublicationStatus::Published
            || ! is_string($publication->external_id) || trim($publication->external_id) === '') {
            $state->status = CatalogSyncStatus::Failed;
            $state->last_error = 'Catalog sync target is missing or no longer eligible.';
            $state->save();

            return;
        }
        $connection = $publication->connection;
        if ($connection === null || $connection->status !== ConnectionStatus::Active
            || ! (bool) data_get($connection->metadata, 'catalog_sync_enabled', false)) {
            $state->status = CatalogSyncStatus::Disabled;
            $state->last_error = null;
            $state->save();

            return;
        }
        if (! (bool) config('omnichannel.catalog_sync.enabled', false)) {
            return;
        }
        $driver = $channels->driverForConnection($connection);
        if (! $driver->capabilities()->supports(Capability::PriceAndStock)) {
            $state->status = CatalogSyncStatus::Failed;
            $state->last_error = 'The connected driver does not support price and stock sync.';
            $state->save();

            return;
        }

        // No provider mutation is implemented until its catalog update contract is verified.
        $state->status = CatalogSyncStatus::Failed;
        $state->attempts++;
        $state->last_error = 'Provider catalog sync is not implemented.';
        $state->save();
    }
}

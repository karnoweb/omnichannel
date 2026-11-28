<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Karnoweb\Omnichannel\Enums\Capability;
use Karnoweb\Omnichannel\Enums\CatalogSyncStatus;
use Karnoweb\Omnichannel\Enums\ConnectionStatus;
use Karnoweb\Omnichannel\Models\CatalogSyncState;
use Karnoweb\Omnichannel\Models\Publication;
use Karnoweb\Omnichannel\Services\CatalogSyncExecutor;
use Karnoweb\Omnichannel\Services\CatalogSyncPolicy;
use Karnoweb\Omnichannel\Services\ChannelManager;
use Karnoweb\Omnichannel\ValueObjects\CatalogListingIdentity;

final class DispatchPendingCatalogSyncsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly int $catalogSyncStateId,
        public readonly ?string $claimedRevision = null,
    ) {}

    public function handle(ChannelManager $channels, CatalogSyncPolicy $policy): void
    {
        if (! $policy->isGloballyEnabled()) {
            return;
        }

        DB::transaction(function () use ($channels, $policy): void {
            /** @var CatalogSyncState|null $state */
            $state = CatalogSyncState::query()->lockForUpdate()->find($this->catalogSyncStateId);
            if ($state === null || $state->status === CatalogSyncStatus::Synced) {
                return;
            }

            if ($this->claimedRevision !== null
                && data_get($state->metadata, 'desired_revision') !== $this->claimedRevision) {
                return;
            }

            if (! in_array($state->status, [CatalogSyncStatus::Pending, CatalogSyncStatus::Failed, CatalogSyncStatus::Dispatching], true)) {
                return;
            }

            $publication = Publication::query()->with(['connection.channel'])->find($state->publication_id);
            if ($publication === null) {
                $state->status = CatalogSyncStatus::Failed;
                $state->last_error = 'Catalog sync target is missing or no longer eligible.';
                $state->save();

                return;
            }

            if (! $policy->publicationEligible($publication)) {
                $state->status = CatalogSyncStatus::Disabled;
                $state->last_error = null;
                $state->save();

                return;
            }

            $identity = new CatalogListingIdentity(
                (int) $publication->channel_connection_id,
                (string) $publication->product_id,
                $publication->external_id,
            );
            if ($state->branch_id !== $publication->branch_id || $state->listing_identity !== $identity->key()) {
                $state->status = CatalogSyncStatus::Failed;
                $state->last_error = 'Catalog sync listing identity no longer matches the publication.';
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

            $driver = $channels->driverForConnection($connection);
            if (! $driver->capabilities()->supports(Capability::PriceAndStock)) {
                $state->status = CatalogSyncStatus::Failed;
                $state->last_error = 'The connected driver does not support price and stock sync.';
                $state->save();

                return;
            }

            $state->status = CatalogSyncStatus::Dispatching;
            $state->last_dispatched_at = now();
            $state->save();

            $lockedRevision = data_get($state->metadata, 'desired_revision');
            app(CatalogSyncExecutor::class)->sync($state, $publication, $driver);

            $state->refresh();
            if ($lockedRevision !== null && data_get($state->metadata, 'desired_revision') !== $lockedRevision
                && $state->status === CatalogSyncStatus::Pending) {
                return;
            }
        });
    }
}

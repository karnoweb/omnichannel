<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Services;

use Karnoweb\Omnichannel\Enums\CatalogSyncStatus;
use Karnoweb\Omnichannel\Jobs\DispatchPendingCatalogSyncsJob;
use Karnoweb\Omnichannel\Models\CatalogSyncState;

final class CatalogSyncDispatcher
{
    public function dispatchPending(CatalogSyncState $state): void
    {
        if (! (bool) config('omnichannel.catalog_sync.enabled', false)) {
            $state->status = CatalogSyncStatus::Disabled;
            $state->save();

            return;
        }
        $state->status = CatalogSyncStatus::Pending;
        $state->last_dispatched_at = now();
        $state->save();

        $delay = max(0, (int) config('omnichannel.catalog_sync.debounce_seconds', 30));
        $revision = data_get($state->metadata, 'desired_revision');
        $claimedRevision = is_string($revision) ? $revision : null;
        $pending = DispatchPendingCatalogSyncsJob::dispatch($state->getKey(), $claimedRevision);
        if ($delay > 0) {
            $pending->delay(now()->addSeconds($delay));
        }
    }
}

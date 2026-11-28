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
        DispatchPendingCatalogSyncsJob::dispatch($state->getKey());
    }
}

<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Karnoweb\Omnichannel\Enums\CatalogSyncStatus;
use Karnoweb\Omnichannel\Models\CatalogSyncState;

final class DispatchPendingCatalogSyncsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly int $catalogSyncStateId) {}

    public function handle(): void
    {
        if (! (bool) config('omnichannel.catalog_sync.enabled', false)) {
            return;
        }
        $state = CatalogSyncState::query()->find($this->catalogSyncStateId);
        if ($state === null || $state->status === CatalogSyncStatus::Synced) {
            return;
        }
        $state->status = CatalogSyncStatus::Dispatching;
        $state->attempts++;
        $state->save();
        $state->status = CatalogSyncStatus::Synced;
        $state->last_synced_at = now();
        $state->last_error = null;
        $state->save();
    }
}
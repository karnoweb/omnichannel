<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Services;

use Karnoweb\Omnichannel\Enums\CatalogSyncStatus;
use Karnoweb\Omnichannel\Models\CatalogSyncState;

final class CatalogSyncRecoveryService
{
    public function __construct(private readonly CatalogSyncDispatcher $dispatcher) {}

    public function recoverStale(): int
    {
        $minutes = (int) config('omnichannel.catalog_sync.retry_window_minutes', 120);
        $cutoff = now()->subMinutes(max(5, $minutes));
        $count = 0;
        CatalogSyncState::query()
            ->whereIn('status', [CatalogSyncStatus::Pending, CatalogSyncStatus::Dispatching, CatalogSyncStatus::Failed])
            ->where(function ($query) use ($cutoff): void {
                $query->whereNull('last_dispatched_at')->orWhere('last_dispatched_at', '<', $cutoff);
            })
            ->orderBy('id')
            ->limit((int) config('omnichannel.catalog_sync.batch_limit', 100))
            ->each(function (CatalogSyncState $state) use (&$count): void {
                $this->dispatcher->dispatchPending($state);
                $count++;
            });

        return $count;
    }
}

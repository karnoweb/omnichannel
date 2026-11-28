<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Services;

use Karnoweb\Omnichannel\Enums\CatalogSyncStatus;
use Karnoweb\Omnichannel\Models\CatalogSyncState;
use Karnoweb\Omnichannel\Models\Publication;

final class CatalogSyncRecoveryService
{
    public function __construct(
        private readonly CatalogSyncDispatcher $dispatcher,
        private readonly CatalogSyncPolicy $policy,
    ) {}

    public function recoverStale(): int
    {
        if (! $this->policy->isGloballyEnabled()) {
            return 0;
        }

        $minutes = (int) config('omnichannel.catalog_sync.retry_window_minutes', 120);
        $cutoff = now()->subMinutes(max(5, $minutes));
        $maxAttempts = (int) config('omnichannel.queue.tries', 5);
        $count = 0;

        CatalogSyncState::query()
            ->whereIn('status', [CatalogSyncStatus::Pending, CatalogSyncStatus::Dispatching, CatalogSyncStatus::Failed])
            ->where(function ($query) use ($cutoff): void {
                $query->whereNull('last_dispatched_at')->orWhere('last_dispatched_at', '<', $cutoff);
            })
            ->where('attempts', '<', $maxAttempts)
            ->orderBy('id')
            ->limit((int) config('omnichannel.catalog_sync.batch_limit', 100))
            ->each(function (CatalogSyncState $state) use (&$count): void {
                /** @var Publication|null $publication */
                $publication = Publication::query()->with(['connection.channel'])->find($state->publication_id);
                if ($publication === null || ! $this->policy->publicationEligible($publication)) {
                    $state->status = CatalogSyncStatus::Disabled;
                    $state->last_error = null;
                    $state->save();

                    return;
                }

                $this->dispatcher->dispatchPending($state);
                $count++;
            });

        return $count;
    }
}

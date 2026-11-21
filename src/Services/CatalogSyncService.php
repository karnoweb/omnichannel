<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Services;

use Karnoweb\Omnichannel\Enums\CatalogSyncStatus;
use Karnoweb\Omnichannel\Models\CatalogSyncState;
use Karnoweb\Omnichannel\Models\Publication;
use Karnoweb\Omnichannel\ValueObjects\CatalogListingIdentity;

final class CatalogSyncService
{
    public function __construct(
        private readonly CatalogSyncPolicy $policy,
        private readonly CatalogSyncDispatcher $dispatcher,
    ) {}

    /**
     * @param  array{price?: int|float|null, stock?: int|null}  $snapshot
     */
    public function recordProductChange(Publication $publication, array $snapshot): ?CatalogSyncState
    {
        if (! $this->policy->publicationEligible($publication)) {
            return null;
        }
        $identity = new CatalogListingIdentity(
            (int) $publication->channel_connection_id,
            (string) $publication->product_id,
            $publication->external_id,
        );
        $priceHash = isset($snapshot['price']) ? crc32((string) $snapshot['price']) : null;
        $stockHash = isset($snapshot['stock']) ? crc32((string) $snapshot['stock']) : null;
        /** @var CatalogSyncState $state */
        $state = CatalogSyncState::query()->firstOrCreate(
            ['publication_id' => $publication->getKey()],
            [
                'branch_id' => $publication->branch_id,
                'listing_identity' => $identity->key(),
                'status' => CatalogSyncStatus::Idle,
            ],
        );
        if ($state->price_hash === $priceHash && $state->stock_hash === $stockHash) {
            return $state;
        }
        $state->price_hash = $priceHash;
        $state->stock_hash = $stockHash;
        $state->status = CatalogSyncStatus::Pending;
        $state->save();
        $this->dispatcher->dispatchPending($state);

        return $state;
    }
}
<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Services;

use Karnoweb\Omnichannel\Enums\CatalogSyncStatus;
use Karnoweb\Omnichannel\Models\CatalogSyncState;
use Karnoweb\Omnichannel\Models\Publication;

final class CatalogSyncReconciliationService
{
    public function convergeAfterExternalConfirmation(Publication $publication): bool
    {
        /** @var CatalogSyncState|null $state */
        $state = CatalogSyncState::query()->where('publication_id', $publication->getKey())->first();
        if ($state === null || $state->status !== CatalogSyncStatus::Unknown) {
            return false;
        }

        $desired = data_get($state->metadata, 'desired_snapshot');
        if (! is_array($desired)) {
            return false;
        }

        $revision = data_get($state->metadata, 'desired_revision');
        $metadata = (array) $state->metadata;
        $metadata['applied_snapshot'] = $desired;
        if (is_string($revision)) {
            $metadata['applied_revision'] = $revision;
        }

        $state->forceFill([
            'status' => CatalogSyncStatus::Synced,
            'last_synced_at' => now(),
            'last_error' => null,
            'metadata' => $metadata,
        ])->save();

        return true;
    }
}

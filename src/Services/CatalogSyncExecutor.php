<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Services;

use Karnoweb\Omnichannel\Contracts\ChannelDriver;
use Karnoweb\Omnichannel\Enums\CatalogSyncStatus;
use Karnoweb\Omnichannel\Enums\FailureClass;
use Karnoweb\Omnichannel\Models\CatalogSyncState;
use Karnoweb\Omnichannel\Models\Publication;

final class CatalogSyncExecutor
{
    public function __construct(
        private readonly CatalogSyncSnapshotApplier $applier,
    ) {}

    public function sync(CatalogSyncState $state, Publication $publication, ChannelDriver $driver): void
    {
        /** @var array<string, mixed>|null $snapshot */
        $snapshot = data_get($state->metadata, 'desired_snapshot');

        if (! is_array($snapshot) || ! $this->applier->hasCommerceFields($snapshot)) {
            $this->markFailed($state, 'Catalog sync snapshot is missing price or stock fields.');

            return;
        }

        $revisionFingerprint = $this->revisionFingerprint($state);

        $this->applier->apply($publication, $snapshot);
        $publication->save();

        $result = $driver->update($publication);

        $state->refresh();

        if ($revisionFingerprint !== $this->revisionFingerprint($state)) {
            $state->status = CatalogSyncStatus::Pending;
            $state->save();

            return;
        }

        if ($result->successful) {
            $metadata = (array) $state->metadata;
            $metadata['applied_snapshot'] = $snapshot;
            $state->forceFill([
                'status' => CatalogSyncStatus::Synced,
                'last_synced_at' => now(),
                'last_error' => null,
                'metadata' => $metadata,
            ])->save();

            return;
        }

        if ($result->failureClass === FailureClass::UnknownOutcome) {
            $state->forceFill([
                'status' => CatalogSyncStatus::Unknown,
                'last_error' => $result->errorMessage,
                'attempts' => $state->attempts + 1,
            ])->save();

            return;
        }

        $this->markFailed(
            $state,
            $result->errorMessage ?? 'Catalog sync provider update failed.',
        );
    }

    private function markFailed(CatalogSyncState $state, string $message): void
    {
        $state->forceFill([
            'status' => CatalogSyncStatus::Failed,
            'last_error' => $message,
            'attempts' => $state->attempts + 1,
        ])->save();
    }

    private function revisionFingerprint(CatalogSyncState $state): string
    {
        return implode('|', [
            (string) ($state->price_hash ?? 'null'),
            (string) ($state->stock_hash ?? 'null'),
            json_encode(data_get($state->metadata, 'desired_snapshot'), JSON_THROW_ON_ERROR),
        ]);
    }
}

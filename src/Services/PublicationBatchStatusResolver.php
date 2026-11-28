<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Services;

use Karnoweb\Omnichannel\Enums\PublicationBatchStatus;
use Karnoweb\Omnichannel\Enums\PublicationStatus;
use Karnoweb\Omnichannel\Models\PublicationBatch;

final class PublicationBatchStatusResolver
{
    public function resolve(PublicationBatch $batch): PublicationBatchStatus
    {
        $batch->loadMissing('items.publication');
        $statuses = $batch->items
            ->map(static fn ($item) => $item->publication?->status)
            ->filter()
            ->values();
        if ($statuses->isEmpty()) {
            return PublicationBatchStatus::Draft;
        }
        if ($statuses->every(static fn (PublicationStatus $s) => $s === PublicationStatus::Published)) {
            return PublicationBatchStatus::Completed;
        }
        if ($statuses->every(static fn (PublicationStatus $s) => $s === PublicationStatus::Cancelled)) {
            return PublicationBatchStatus::Cancelled;
        }
        if ($statuses->every(static fn (PublicationStatus $s) => $s === PublicationStatus::Draft)) {
            return PublicationBatchStatus::Draft;
        }
        if ($statuses->every(static fn (PublicationStatus $s) => $s === PublicationStatus::Scheduled)) {
            return PublicationBatchStatus::Scheduled;
        }
        if ($statuses->contains(static fn (PublicationStatus $s) => $s === PublicationStatus::UnknownOutcome)) {
            return PublicationBatchStatus::RequiresAttention;
        }
        if ($statuses->contains(static fn (PublicationStatus $s) => $s->isInFlight())) {
            return PublicationBatchStatus::InProgress;
        }
        $hasPublished = $statuses->contains(static fn (PublicationStatus $s) => $s === PublicationStatus::Published);
        $hasFailed = $statuses->contains(static fn (PublicationStatus $s) => $s === PublicationStatus::Failed);
        if ($hasPublished && $hasFailed) {
            return PublicationBatchStatus::PartiallyCompleted;
        }
        if ($statuses->every(static fn (PublicationStatus $s) => $s === PublicationStatus::Failed)) {
            return PublicationBatchStatus::Failed;
        }
        if ($hasPublished || $hasFailed) {
            return PublicationBatchStatus::PartiallyCompleted;
        }

        return PublicationBatchStatus::InProgress;
    }
}

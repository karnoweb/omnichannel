<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Services;

use Illuminate\Support\Facades\DB;
use Karnoweb\Omnichannel\Data\CreatePublicationBatchData;
use Karnoweb\Omnichannel\Data\CreatePublicationData;
use Karnoweb\Omnichannel\Enums\PublicationBatchDispatchMode;
use Karnoweb\Omnichannel\Enums\PublicationBatchStatus;
use Karnoweb\Omnichannel\Enums\PublicationSourceType;
use Karnoweb\Omnichannel\Events\PublicationBatchCompleted;
use Karnoweb\Omnichannel\Events\PublicationBatchCreated;
use Karnoweb\Omnichannel\Events\PublicationBatchDispatched;
use Karnoweb\Omnichannel\Events\PublicationBatchStatusChanged;
use Karnoweb\Omnichannel\Exceptions\BranchAccessDeniedException;
use Karnoweb\Omnichannel\Jobs\PublishPublicationJob;
use Karnoweb\Omnichannel\Models\ChannelConnection;
use Karnoweb\Omnichannel\Models\Publication;
use Karnoweb\Omnichannel\Models\PublicationBatch;
use Karnoweb\Omnichannel\Models\PublicationBatchItem;
use Karnoweb\Omnichannel\Support\OmnichannelContext;
use Karnoweb\Omnichannel\Support\OmnichannelEventDispatcher;
use RuntimeException;

final class PublicationBatchService
{
    public function __construct(
        private readonly PublicationCreationService $publications,
        private readonly PublicationContentMerger $merger,
        private readonly PublicationBatchStatusResolver $statusResolver,
        private readonly OmnichannelContext $context,
    ) {}

    public function create(CreatePublicationBatchData $data): PublicationBatch
    {
        $branchId = $this->context->branchId();
        if ($branchId === null) {
            throw new BranchAccessDeniedException;
        }
        $this->validate($data, $branchId);
        $fingerprint = $this->fingerprint($data);
        if ($data->intentId !== null) {
            $existing = PublicationBatch::query()
                ->where('branch_id', $branchId)
                ->where('intent_id', $data->intentId)
                ->first();
            if ($existing !== null) {
                if ($existing->request_fingerprint !== $fingerprint) {
                    throw new RuntimeException('Batch intent conflicts with a different payload.');
                }

                return $existing->load('items.publication');
            }
        }

        return DB::transaction(function () use ($data, $branchId, $fingerprint): PublicationBatch {
            $batch = PublicationBatch::query()->create([
                'branch_id' => $branchId,
                'created_by' => $data->actorId ?? $this->context->userId(),
                'title' => $data->title,
                'source_type' => $data->sourceType,
                'source_reference_type' => $data->sourceReferenceType,
                'source_reference_id' => $data->sourceReferenceId !== null ? (string) $data->sourceReferenceId : null,
                'product_id' => $data->productId,
                'base_content' => $data->baseContent->toArray(),
                'intent_id' => $data->intentId,
                'request_fingerprint' => $fingerprint,
                'dispatch_mode' => $data->dispatchMode,
                'status' => PublicationBatchStatus::Draft,
                'metadata' => [],
            ]);
            $connectionIds = [];
            foreach ($data->targets as $target) {
                if (in_array($target->connectionId, $connectionIds, true)) {
                    throw new RuntimeException('Duplicate connection in batch targets.');
                }
                $connectionIds[] = $target->connectionId;
                /** @var ChannelConnection $connection */
                $connection = ChannelConnection::query()->findOrFail($target->connectionId);
                $this->context->assertBranchAccess($connection->branch_id);
                $content = $this->merger->merge($data->baseContent, $target->contentOverride);
                $scheduledAt = $target->scheduledAt ?? $data->scheduledAt;
                $publication = $this->publications->create(match ($data->sourceType) {
                    PublicationSourceType::Product => new CreatePublicationData(
                        sourceType: PublicationSourceType::Product,
                        connection: $connection,
                        type: $target->type,
                        content: $content,
                        productId: (string) $data->productId,
                        scheduledAt: $scheduledAt,
                        intentId: $data->intentId,
                    ),
                    PublicationSourceType::FreeForm => new CreatePublicationData(
                        sourceType: PublicationSourceType::FreeForm,
                        connection: $connection,
                        type: $target->type,
                        content: $content,
                        sourceTitle: $data->sourceTitle ?? $data->title,
                        scheduledAt: $scheduledAt,
                        intentId: $data->intentId,
                    ),
                    PublicationSourceType::Reference => new CreatePublicationData(
                        sourceType: PublicationSourceType::Reference,
                        connection: $connection,
                        type: $target->type,
                        content: $content,
                        sourceReferenceType: (string) $data->sourceReferenceType,
                        sourceReferenceId: $data->sourceReferenceId,
                        sourceTitle: $data->sourceTitle,
                        scheduledAt: $scheduledAt,
                        intentId: $data->intentId,
                    ),
                });
                PublicationBatchItem::query()->create([
                    'batch_id' => $batch->getKey(),
                    'publication_id' => $publication->getKey(),
                    'channel_connection_id' => $connection->getKey(),
                    'publication_type' => $target->type,
                    'scheduled_at' => $scheduledAt,
                    'sort_order' => $target->sortOrder,
                ]);
            }
            OmnichannelEventDispatcher::dispatch(new PublicationBatchCreated($batch));
            if ($data->dispatchMode === PublicationBatchDispatchMode::Now) {
                $this->dispatchAfterCommit($batch);
            }

            return $batch->load('items.publication');
        });
    }

    public function dispatch(PublicationBatch $batch, bool $mockOnly = false): void
    {
        $batch->loadMissing('items.publication');
        if ($mockOnly) {
            $this->refreshStatus($batch);

            return;
        }
        foreach ($batch->items as $item) {
            /** @var Publication|null $publication */
            $publication = $item->publication;
            if ($publication === null || $publication->isPublished()) {
                continue;
            }
            PublishPublicationJob::dispatch($publication->getKey());
        }
        OmnichannelEventDispatcher::dispatch(new PublicationBatchDispatched($batch));
        $this->refreshStatus($batch);
    }

    public function refreshStatus(PublicationBatch $batch): PublicationBatch
    {
        $previous = $batch->status;
        $resolved = $this->statusResolver->resolve($batch);
        if ($previous !== $resolved) {
            $batch->status = $resolved;
            $batch->save();
            OmnichannelEventDispatcher::dispatch(new PublicationBatchStatusChanged($batch, $previous, $resolved));
            if (in_array($resolved, [PublicationBatchStatus::Completed, PublicationBatchStatus::PartiallyCompleted, PublicationBatchStatus::Failed], true)) {
                OmnichannelEventDispatcher::dispatch(new PublicationBatchCompleted($batch));
            }
        }

        return $batch;
    }

    private function dispatchAfterCommit(PublicationBatch $batch): void
    {
        DB::afterCommit(function () use ($batch): void {
            $this->dispatch($batch);
        });
    }

    private function validate(CreatePublicationBatchData $data, int|string $branchId): void
    {
        $limit = (int) config('omnichannel.publication_batch.max_targets', 20);
        if (count($data->targets) < 1) {
            throw new RuntimeException('Batch requires at least one target.');
        }
        if (count($data->targets) > $limit) {
            throw new RuntimeException('Batch exceeds configured target limit.');
        }
        if ($data->sourceType === PublicationSourceType::Product && ($data->productId === null || $data->productId === '')) {
            throw new RuntimeException('Product batches require product id.');
        }
    }

    private function fingerprint(CreatePublicationBatchData $data): string
    {
        $material = [
            'source' => $data->sourceType->value,
            'product' => $data->productId,
            'content' => $data->baseContent->toArray(),
            'targets' => array_map(static fn ($t) => [
                'connection' => $t->connectionId,
                'type' => $t->type->value,
                'override' => $t->contentOverride?->toArray(),
            ], $data->targets),
        ];

        return hash('sha256', (string) json_encode($material, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}

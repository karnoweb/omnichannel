<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Services;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Karnoweb\Omnichannel\Contracts\ReconcilableChannelDriver;
use Karnoweb\Omnichannel\Enums\FailureClass;
use Karnoweb\Omnichannel\Enums\LogOperation;
use Karnoweb\Omnichannel\Enums\PublicationStatus;
use Karnoweb\Omnichannel\Models\Publication;
use Karnoweb\Omnichannel\Omnichannel;
use Karnoweb\Omnichannel\Results\ReconciliationResult;
use Karnoweb\Omnichannel\Support\OmnichannelContext;
use RuntimeException;

final class PublicationRecoveryService
{
    public function __construct(
        private readonly ChannelManager $channels,
        private readonly PublicationLogger $logger,
        private readonly OmnichannelContext $context,
        private readonly ApiRequestRateLimitScope $apiRateLimitScope,
    ) {}

    /**
     * @return Collection<int, Publication>
     */
    public function stuckPublications(DateTimeInterface $before, ?int $branchId = null, ?int $channelId = null, int $limit = 100): Collection
    {
        /** @var class-string<Publication> $model */
        $model = Omnichannel::model('publication');
        $query = $model::query()->stuckInFlight($before);
        if ($branchId !== null) {
            $query->where('branch_id', $branchId);
        }
        if ($channelId !== null) {
            $query->where('channel_id', $channelId);
        }

        return $query->orderBy('id')->limit($limit)->get();
    }

    /**
     * @param  array<string, mixed>  $evidence
     */
    public function recordManualDecision(
        Publication $publication,
        string $outcome,
        string $reason,
        array $evidence = [],
        int|string|null $actorId = null,
    ): ReconciliationResult {
        if (! in_array($outcome, [
            ReconciliationResult::ConfirmedSuccess,
            ReconciliationResult::ConfirmedFailure,
            ReconciliationResult::Pending,
            ReconciliationResult::PartialSuccess,
            ReconciliationResult::Unknown,
        ], true)) {
            throw new RuntimeException('Unsupported manual reconciliation outcome.');
        }

        $publication->refresh();
        $this->context->assertBranchAccess($publication->branch_id);
        $actor = $actorId ?? $this->context->userId();

        return DB::transaction(function () use ($publication, $outcome, $reason, $evidence, $actor): ReconciliationResult {
            $metadata = $publication->metadata ?? [];
            data_set($metadata, 'omnichannel.recovery', [
                'outcome' => $outcome,
                'reason' => $reason,
                'evidence' => $evidence,
                'actor_id' => $actor,
                'recorded_at' => now()->toIso8601String(),
                'manual' => true,
            ]);

            if ($outcome === ReconciliationResult::ConfirmedSuccess && $publication->status === PublicationStatus::Publishing) {
                $externalId = is_string($evidence['external_id'] ?? null) ? $evidence['external_id'] : null;
                if ($externalId === null || $externalId === '') {
                    throw new RuntimeException('Confirmed success requires a valid external_id in evidence.');
                }
                $publication->markPublished($externalId, is_string($evidence['external_url'] ?? null) ? $evidence['external_url'] : null);
            } elseif ($outcome === ReconciliationResult::ConfirmedFailure) {
                $publication->markFailed(
                    FailureClass::Unknown,
                    'recovery.confirmed_failure',
                    $reason,
                );
            } elseif ($outcome === ReconciliationResult::Unknown) {
                $publication->markUnknownOutcome('recovery.manual_unknown', $reason);
            } else {
                $publication->forceFill(['metadata' => $metadata])->save();
            }

            $log = $this->logger->start(
                LogOperation::Recovery,
                publication: $publication,
                connection: $publication->connection,
                request: ['outcome' => $outcome, 'reason' => $reason],
            );
            $this->logger->succeed($log, $evidence);

            return new ReconciliationResult($outcome, $reason, $evidence);
        });
    }

    public function attemptDriverReconciliation(Publication $publication): ReconciliationResult
    {
        $publication->loadMissing('connection.channel');
        $driver = $this->channels->driverForPublication($publication);
        if (! $driver instanceof ReconcilableChannelDriver) {
            return new ReconciliationResult(
                ReconciliationResult::Unsupported,
                'This channel does not support automated reconciliation.',
            );
        }

        $operation = (string) data_get($publication->metadata, 'omnichannel.active_operation.type', 'publish');

        return $this->apiRateLimitScope->using(
            $publication->connection,
            fn () => $driver->reconcilePublication($publication, $operation),
        );
    }
}

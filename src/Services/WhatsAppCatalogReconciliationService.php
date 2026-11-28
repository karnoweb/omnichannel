<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Services;

use Karnoweb\Omnichannel\Channels\WhatsApp\WhatsAppCatalogClient;
use Karnoweb\Omnichannel\Models\Publication;
use Karnoweb\Omnichannel\Results\ReconciliationResult;

final class WhatsAppCatalogReconciliationService
{
    public function __construct(
        private readonly WhatsAppCatalogClient $client,
        private readonly SecretRedactor $redactor,
        private readonly ApiRequestRateLimitScope $scope,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function inspectPendingBatches(Publication $publication, ?string $onlyOperation = null): array
    {
        $publication->loadMissing('connection.channel');
        if ($publication->connection === null || $publication->connection->channel?->driver !== 'whatsapp') {
            return [];
        }

        $token = $publication->connection->credential('access_token');
        $catalogId = $publication->connection->credential('catalog_id')
            ?? data_get($publication->connection->metadata, 'catalog_id')
            ?? $publication->connection->external_account_id;
        if (! is_string($token) || $token === '' || ! is_scalar($catalogId) || (string) $catalogId === '') {
            return [];
        }

        $operations = $onlyOperation === null
            ? ['create', 'update', 'delete', 'deactivate']
            : [(string) $onlyOperation];

        $rows = [];
        foreach ($operations as $operationName) {
            $pending = $publication->persistentArtifact($operationName, 'pending_batch');
            if (! is_array($pending)) {
                continue;
            }
            $handles = $pending['handles'] ?? [];
            if (! is_array($handles) || ! array_is_list($handles) || $handles === []) {
                $rows[] = [
                    'operation' => $operationName,
                    'handle' => null,
                    'lookup_successful' => false,
                    'status' => 'no_handle',
                ];

                continue;
            }

            $statuses = [];
            foreach ($handles as $handle) {
                if (! is_string($handle) || trim($handle) === '') {
                    $rows[] = [
                        'operation' => $operationName,
                        'handle' => null,
                        'lookup_successful' => false,
                        'status' => 'invalid_handle',
                    ];

                    continue;
                }
                $response = $this->scope->using(
                    $publication->connection,
                    fn () => $this->client->checkBatchRequestStatus((string) $catalogId, $handle, $token),
                );
                $batchRows = $response->get('data');
                $batchRow = is_array($batchRows) && array_is_list($batchRows) && count($batchRows) === 1
                    ? $batchRows[0]
                    : [];
                $summary = [
                    'handle' => $handle,
                    'checked_at' => now()->toIso8601String(),
                    'lookup_successful' => ! $response->failed(),
                    'handle_matches' => data_get($batchRow, 'handle') === $handle,
                    'status' => is_array($batchRow) ? ($batchRow['status'] ?? null) : null,
                    'errors_total_count' => is_array($batchRow) ? ($batchRow['errors_total_count'] ?? null) : null,
                    'errors' => data_get($batchRow, 'errors', []),
                    'ids_of_invalid_requests' => data_get($batchRow, 'ids_of_invalid_requests', []),
                ];
                if ($response->failed()) {
                    $summary['http_error'] = $response->toLogArray();
                }
                $safeSummary = $this->redactor->redactAndTrim($summary, 8192, $publication->connection->credentials ?? []);
                $statuses[] = $safeSummary;
                $rows[] = ['operation' => $operationName, ...$safeSummary];
            }

            $pending['status_checks'] = $statuses;
            $publication->rememberPersistentArtifact($operationName, 'pending_batch', $pending);
        }

        return $rows;
    }

    public function finalizeWhenReady(Publication $publication): ReconciliationResult
    {
        if (! (bool) config('omnichannel.whatsapp_catalog.auto_reconcile', false)) {
            return new ReconciliationResult(ReconciliationResult::Pending, 'Automatic WhatsApp batch finalization is disabled.');
        }

        $checks = $this->inspectPendingBatches($publication);
        if ($checks === []) {
            return new ReconciliationResult(ReconciliationResult::Pending, 'No pending WhatsApp batches.');
        }

        $pending = false;
        $failed = false;
        $succeeded = false;
        foreach ($checks as $check) {
            $status = $check['status'] ?? null;
            if (($check['lookup_successful'] ?? false) !== true || ($check['handle_matches'] ?? false) !== true) {
                return new ReconciliationResult(ReconciliationResult::Unknown, 'Batch handle or status evidence is missing.');
            }
            if (in_array($status, ['pending', 'in_progress'], true)) {
                $pending = true;

                continue;
            }
            $errorCount = $check['errors_total_count'] ?? null;
            if (! in_array($status, ['finished', 'completed'], true) || ! is_int($errorCount) || $errorCount < 0
                || ($errorCount === 0 && (($check['errors'] ?? null) !== [] || ($check['ids_of_invalid_requests'] ?? null) !== []))) {
                return new ReconciliationResult(ReconciliationResult::Unknown, 'Batch result is incomplete or inconsistent.');
            }
            $failed = $failed || $errorCount > 0;
            $succeeded = $succeeded || $errorCount === 0;
        }

        if ($pending) {
            return new ReconciliationResult(ReconciliationResult::Pending, 'Batch processing still in progress.');
        }

        if ($failed) {
            return new ReconciliationResult(
                $succeeded ? ReconciliationResult::PartialSuccess : ReconciliationResult::ConfirmedFailure,
                'At least one batch handle reported item errors.',
                ['checks' => $checks],
            );
        }

        app(CatalogSyncReconciliationService::class)->convergeAfterExternalConfirmation($publication);

        return new ReconciliationResult(
            ReconciliationResult::ConfirmedSuccess,
            'All inspected batch handles reported completion.',
            ['checks' => $checks],
        );
    }
}

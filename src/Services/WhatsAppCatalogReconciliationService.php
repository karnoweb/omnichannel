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
            if (! is_array($handles) || $handles === []) {
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
                if (! is_string($handle) || $handle === '') {
                    continue;
                }
                $response = $this->scope->using(
                    $publication->connection,
                    fn () => $this->client->checkBatchRequestStatus((string) $catalogId, $handle, $token),
                );
                $batchRow = data_get($response->data, 'data.0', []);
                $summary = [
                    'handle' => $handle,
                    'checked_at' => now()->toIso8601String(),
                    'lookup_successful' => ! $response->failed(),
                    'status' => is_array($batchRow) ? ($batchRow['status'] ?? null) : null,
                    'errors_total_count' => is_array($batchRow) ? ($batchRow['errors_total_count'] ?? null) : null,
                ];
                if ($response->failed()) {
                    $summary['http_error'] = $response->toLogArray();
                }
                $statuses[] = $this->redactor->redactAndTrim($summary, 8192, $publication->connection->credentials ?? []);
                $rows[] = ['operation' => $operationName, ...$summary];
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

        $allFinished = true;
        foreach ($checks as $check) {
            $status = $check['status'] ?? null;
            if ($status === 'no_handle') {
                continue;
            }
            if (($check['lookup_successful'] ?? false) !== true) {
                return new ReconciliationResult(ReconciliationResult::Unknown, 'Batch status lookup failed.');
            }
            if (! in_array($status, ['finished', 'completed'], true)) {
                $allFinished = false;
            }
        }

        if (! $allFinished) {
            return new ReconciliationResult(ReconciliationResult::Pending, 'Batch processing still in progress.');
        }

        return new ReconciliationResult(
            ReconciliationResult::ConfirmedSuccess,
            'All inspected batch handles reported completion.',
            ['checks' => $checks],
        );
    }
}
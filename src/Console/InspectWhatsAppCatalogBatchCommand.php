<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Console;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Karnoweb\Omnichannel\Channels\WhatsApp\WhatsAppCatalogClient;
use Karnoweb\Omnichannel\Omnichannel;
use Karnoweb\Omnichannel\Services\SecretRedactor;

final class InspectWhatsAppCatalogBatchCommand extends Command
{
    protected $signature = 'omnichannel:inspect-whatsapp-batch {publication : Publication record id} {--operation= : create, update, delete, or deactivate}';

    protected $description = 'Inspect and persist the remote status of a pending WhatsApp catalog batch';

    public function handle(WhatsAppCatalogClient $client, SecretRedactor $redactor): int
    {
        $model = Omnichannel::model('publication');
        if (! is_subclass_of($model, Model::class)) {
            $this->error('Configured publication model is invalid.');

            return self::FAILURE;
        }

        $publication = $model::query()->with('connection')->find($this->argument('publication'));
        if ($publication === null || $publication->connection === null || $publication->connection->channel?->driver !== 'whatsapp') {
            $this->error('WhatsApp publication was not found.');

            return self::FAILURE;
        }

        $operation = $this->option('operation');
        $operations = $operation === null
            ? ['create', 'update', 'delete', 'deactivate']
            : [(string) $operation];

        if (array_diff($operations, ['create', 'update', 'delete', 'deactivate']) !== []) {
            $this->error('Operation must be create, update, delete, or deactivate.');

            return self::FAILURE;
        }

        $token = $publication->connection->credential('access_token');
        $catalogId = $publication->connection->credential('catalog_id')
            ?? data_get($publication->connection->metadata, 'catalog_id')
            ?? $publication->connection->external_account_id;
        if (! is_string($token) || $token === '' || ! is_scalar($catalogId) || (string) $catalogId === '') {
            $this->error('WhatsApp connection is missing its access token or catalog id.');

            return self::FAILURE;
        }

        $inspected = 0;
        $failures = 0;
        foreach ($operations as $operationName) {
            $pending = $publication->persistentArtifact($operationName, 'pending_batch');
            if (! is_array($pending)) {
                continue;
            }
            $handles = $pending['handles'] ?? [];
            if (! is_array($handles) || $handles === []) {
                $this->warn("{$operationName}: no batch handle was returned; status cannot be queried.");
                $failures++;

                continue;
            }

            $statuses = [];
            foreach ($handles as $handle) {
                if (! is_string($handle) || $handle === '') {
                    continue;
                }
                $response = $client->checkBatchRequestStatus((string) $catalogId, $handle, $token);
                $row = data_get($response->data, 'data.0', []);
                $summary = [
                    'handle' => $handle,
                    'checked_at' => now()->toIso8601String(),
                    'lookup_successful' => ! $response->failed(),
                    'status' => is_array($row) ? ($row['status'] ?? null) : null,
                    'errors_total_count' => is_array($row) ? ($row['errors_total_count'] ?? null) : null,
                    'errors' => is_array($row) ? ($row['errors'] ?? []) : [],
                    'warnings' => is_array($row) ? ($row['warnings'] ?? []) : [],
                ];
                if ($response->failed()) {
                    $summary['http_error'] = $response->toLogArray();
                }
                $statuses[] = [
                    ...$redactor->redactAndTrim($summary, 8192, $publication->connection->credentials ?? []),
                ];
                if ($response->failed()) {
                    $failures++;
                }
            }

            $pending['status_checks'] = $statuses;
            $publication->rememberPersistentArtifact($operationName, 'pending_batch', $pending);
            foreach ($statuses as $status) {
                $this->line(sprintf(
                    '%s %s: %s; errors=%s; lookup=%s',
                    $operationName,
                    $status['handle'],
                    $status['status'] ?? 'unavailable',
                    $status['errors_total_count'] ?? 'unknown',
                    $status['lookup_successful'] ? 'ok' : 'failed',
                ));
                $inspected++;
            }
        }

        if ($inspected === 0 && $failures === 0) {
            $this->info('No pending WhatsApp catalog batches found.');

            return self::SUCCESS;
        }

        $this->comment('Inspection never changes publication status or retries the catalog mutation.');

        return $failures === 0 ? self::SUCCESS : self::FAILURE;
    }
}

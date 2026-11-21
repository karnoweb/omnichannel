<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Services;

use Carbon\CarbonImmutable;
use Karnoweb\Omnichannel\Enums\FailureClass;
use Karnoweb\Omnichannel\Enums\LogOperation;
use Karnoweb\Omnichannel\Enums\LogStatus;
use Karnoweb\Omnichannel\Models\ChannelConnection;
use Karnoweb\Omnichannel\Models\Publication;
use Karnoweb\Omnichannel\Models\PublicationLog;
use Karnoweb\Omnichannel\Omnichannel;
use Karnoweb\Omnichannel\Results\ConnectionTestResult;
use Karnoweb\Omnichannel\Results\DeleteResult;
use Karnoweb\Omnichannel\Results\PublishResult;
use Karnoweb\Omnichannel\Results\ValidationResult;

final class PublicationLogger
{
    public function __construct(private readonly SecretRedactor $redactor) {}

    /**
     * @param  array<string, mixed>  $request
     * @param  array<string, mixed>  $context
     */
    public function start(
        LogOperation $operation,
        ?Publication $publication = null,
        ?ChannelConnection $connection = null,
        array $request = [],
        array $context = [],
        ?int $attempt = null,
    ): PublicationLog {
        /** @var class-string<PublicationLog> $logModel */
        $logModel = Omnichannel::model('publication_log');

        /** @var PublicationLog $log */
        $log = $logModel::query()->create([
            'publication_id' => $publication?->id,
            'channel_connection_id' => $connection instanceof ChannelConnection
                ? $connection->id
                : $publication?->channel_connection_id,
            'operation' => $operation,
            'status' => LogStatus::Started,
            'attempt' => $attempt ?? max(1, $publication instanceof Publication ? $publication->attempts : 1),
            'started_at' => now(),
            'request' => $this->redactor->redactAndTrim($request, 8192, $connection?->credentials ?? $publication?->connection?->credentials ?? []),
            'context' => $this->redactor->redactAndTrim($context, 8192, $connection?->credentials ?? $publication?->connection?->credentials ?? []),
        ]);

        return $log;
    }

    /**
     * @param  array<string, mixed>  $response
     */
    public function succeed(PublicationLog $log, array $response = [], ?string $externalId = null): PublicationLog
    {
        return $this->close($log, LogStatus::Succeeded, $response, [
            'external_id' => $externalId,
        ]);
    }

    /**
     * @param  array<string, mixed>  $response
     */
    public function fail(
        PublicationLog $log,
        FailureClass $failureClass,
        ?string $errorCode,
        ?string $errorMessage,
        array $response = [],
    ): PublicationLog {
        return $this->close($log, LogStatus::Failed, $response, [
            'failure_class' => $failureClass,
            'error_code' => $errorCode,
            'error_message' => $errorMessage !== null ? $this->redactor->redactString($errorMessage, $log->connection?->credentials ?? []) : null,
            'retryable' => $failureClass->isRetryable(),
        ]);
    }

    public function finishWithPublishResult(PublicationLog $log, PublishResult $result): PublicationLog
    {
        return $result->successful
            ? $this->succeed($log, $result->metadata, $result->externalId)
            : $this->fail(
                $log,
                $result->failureClass ?? FailureClass::Unknown,
                $result->errorCode,
                $result->errorMessage,
                $result->metadata,
            );
    }

    public function recordManualReplayAuthorization(Publication $publication): PublicationLog
    {
        $log = $this->start(
            LogOperation::ManualRetry,
            publication: $publication,
            request: [
                'previous_status' => $publication->status->value,
                'explicit_confirmation' => true,
                'automatic_retry' => false,
            ],
            context: ['reason' => 'unknown_outcome_manual_replay'],
        );

        return $this->succeed($log, ['authorized' => true]);
    }

    public function finishWithDeleteResult(PublicationLog $log, DeleteResult $result): PublicationLog
    {
        return $result->successful
            ? $this->succeed($log, $result->metadata)
            : $this->fail(
                $log,
                $result->failureClass ?? FailureClass::Unknown,
                $result->errorCode,
                $result->errorMessage,
                $result->metadata,
            );
    }

    public function finishWithConnectionTest(PublicationLog $log, ConnectionTestResult $result): PublicationLog
    {
        return $result->successful
            ? $this->succeed($log, ['account' => $result->account], $result->accountId)
            : $this->fail(
                $log,
                $result->failureClass ?? FailureClass::Unknown,
                $result->errorCode,
                $result->errorMessage,
            );
    }

    public function recordValidationFailure(
        Publication $publication,
        ValidationResult $validation,
    ): PublicationLog {
        /** @var class-string<PublicationLog> $logModel */
        $logModel = Omnichannel::model('publication_log');

        /** @var PublicationLog $log */
        $log = $logModel::query()->create([
            'publication_id' => $publication->id,
            'channel_connection_id' => $publication->channel_connection_id,
            'operation' => LogOperation::Validate,
            'status' => LogStatus::Skipped,
            'attempt' => max(1, $publication->attempts),
            'started_at' => now(),
            'finished_at' => now(),
            'duration_ms' => 0,
            'failure_class' => FailureClass::Validation,
            'error_code' => $validation->errorCodes()[0] ?? 'validation.failed',
            'error_message' => $this->redactor->redactString($validation->summary(), $publication->connection?->credentials ?? []),
            'retryable' => false,
            'response' => $this->redactor->redactAndTrim($validation->toArray()),
        ]);

        return $log;
    }

    /**
     * @param  array<string, mixed>  $response
     * @param  array<string, mixed>  $attributes
     */
    private function close(
        PublicationLog $log,
        LogStatus $status,
        array $response,
        array $attributes = [],
    ): PublicationLog {
        $finishedAt = CarbonImmutable::now();
        $startedAt = $log->started_at ?? $finishedAt;

        $log->forceFill([
            ...$this->redactor->redact(array_filter($attributes, static fn (mixed $v): bool => $v !== null), 0, $log->connection?->credentials ?? []),
            'error_message' => isset($attributes['error_message'])
                ? $this->redactor->redactString((string) $attributes['error_message'], $log->connection?->credentials ?? [])
                : null,
            'status' => $status,
            'finished_at' => $finishedAt,
            'duration_ms' => max(0, (int) $startedAt->diffInMilliseconds($finishedAt)),
            'response' => $this->redactor->redactAndTrim($response, 8192, $log->connection?->credentials ?? []),
        ])->save();

        return $log;
    }
}

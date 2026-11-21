<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Services;

use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Karnoweb\Omnichannel\Contracts\BranchScopedProductRepository;
use Karnoweb\Omnichannel\Contracts\DeactivatableChannelDriver;
use Karnoweb\Omnichannel\Contracts\ProductRepository;
use Karnoweb\Omnichannel\Enums\Capability;
use Karnoweb\Omnichannel\Enums\ConnectionStatus;
use Karnoweb\Omnichannel\Enums\FailureClass;
use Karnoweb\Omnichannel\Enums\LogOperation;
use Karnoweb\Omnichannel\Enums\PublicationStatus;
use Karnoweb\Omnichannel\Enums\PublicationType;
use Karnoweb\Omnichannel\Events\ConnectionConnected;
use Karnoweb\Omnichannel\Events\PublicationCreated;
use Karnoweb\Omnichannel\Events\PublicationFailed;
use Karnoweb\Omnichannel\Events\PublicationPublished;
use Karnoweb\Omnichannel\Exceptions\BranchAccessDeniedException;
use Karnoweb\Omnichannel\Exceptions\UnsupportedCapabilityException;
use Karnoweb\Omnichannel\Jobs\PublishPublicationJob;
use Karnoweb\Omnichannel\Models\ChannelConnection;
use Karnoweb\Omnichannel\Models\Publication;
use Karnoweb\Omnichannel\Omnichannel;
use Karnoweb\Omnichannel\Results\ConnectionTestResult;
use Karnoweb\Omnichannel\Results\DeleteResult;
use Karnoweb\Omnichannel\Results\PublishResult;
use Karnoweb\Omnichannel\Support\OmnichannelContext;
use Karnoweb\Omnichannel\Support\OmnichannelEventDispatcher;
use Karnoweb\Omnichannel\ValueObjects\PublicationContent;
use RuntimeException;
use Throwable;

final class PublishingService
{
    public function __construct(
        private readonly ChannelManager $channels,
        private readonly PublicationLogger $logger,
        private readonly PublicationCreationService $creation,
        private readonly OmnichannelContext $context,
        private readonly ApiRequestRateLimitScope $apiRateLimitScope,
    ) {}

    /**
     * @throws UnsupportedCapabilityException
     */
    public function create(
        string $productId,
        ChannelConnection $connection,
        PublicationType $type,
        PublicationContent $content,
        ?DateTimeInterface $scheduledAt = null,
        bool $allowDuplicate = false,
        int|string|null $actorId = null,
        ?string $intentId = null,
    ): Publication {
        return $this->creation->createFromProduct(
            $productId,
            $connection,
            $type,
            $content,
            $scheduledAt,
            $allowDuplicate,
            $actorId,
            $intentId,
        );
    }

    public function createFromContent(
        ChannelConnection $connection,
        PublicationType $type,
        PublicationContent $content,
        ?string $sourceTitle = null,
        ?DateTimeInterface $scheduledAt = null,
        bool $allowDuplicate = false,
        int|string|null $actorId = null,
        ?string $intentId = null,
    ): Publication {
        return $this->creation->createFromContent(
            $connection,
            $type,
            $content,
            $sourceTitle,
            $scheduledAt,
            $allowDuplicate,
            $actorId,
            $intentId,
        );
    }

    public function publish(Publication $publication, ?string $attemptToken = null, ?int $expectedScheduledAt = null): PublishResult
    {
        $publication->refresh();
        $publication->loadMissing(['channel', 'connection']);
        $this->assertPublicationAccess($publication);

        if ($expectedScheduledAt !== null && $publication->scheduled_at?->getTimestamp() !== $expectedScheduledAt) {
            return PublishResult::failure(FailureClass::Validation, 'The publication schedule changed.', 'publication.schedule_changed');
        }

        if ($publication->isPublished() && $publication->attempt_token !== null) {
            return PublishResult::failure(
                FailureClass::Transient,
                trans('omnichannel::omnichannel.errors.operation_in_flight'),
                'publication.operation_in_flight',
            );
        }

        if ($publication->isPublished() && $publication->external_id !== null) {
            return PublishResult::success(
                app(SecretRedactor::class)->redactString($publication->external_id, $publication->connection?->credentials ?? []),
                $publication->external_url !== null ? app(SecretRedactor::class)->redactString($publication->external_url, $publication->connection?->credentials ?? []) : null,
                ['skipped' => 'already_published'],
            );
        }

        $connection = $publication->connection;

        if (! $connection->isUsable()) {
            return $this->failWithoutAttempt(
                $publication,
                FailureClass::Authentication,
                'connection.not_usable',
                trans('omnichannel::omnichannel.errors.connection_not_usable', ['name' => $connection->name]),
            );
        }

        $driver = $this->channels->driverForPublication($publication);
        $validation = $driver->validate($publication);

        if ($validation->fails()) {
            $this->logger->recordValidationFailure($publication, $validation);

            return $this->failWithoutAttempt(
                $publication,
                FailureClass::Validation,
                $validation->errorCodes()[0] ?? 'validation.failed',
                $validation->summary(),
            );
        }

        if (! $publication->claimForPublishing($attemptToken)) {
            return PublishResult::failure(
                FailureClass::Transient,
                trans('omnichannel::omnichannel.errors.already_in_flight'),
                'publication.already_in_flight',
            );
        }

        $log = $this->logger->start(
            LogOperation::Publish,
            publication: $publication,
            connection: $connection,
            request: [
                'driver' => $driver->key(),
                'type' => $publication->type->value,
                'media_count' => $publication->content()->mediaCount(),
                'idempotency_key' => $publication->idempotency_key,
            ],
            attempt: $publication->attempts,
        );

        try {
            $result = $this->apiRateLimitScope->using($connection, fn () => $driver->publish($publication));
        } catch (Throwable $e) {
            $result = PublishResult::failure(
                FailureClass::UnknownOutcome,
                app(SecretRedactor::class)->redactString($e->getMessage(), $publication->connection?->credentials ?? []),
                'driver.exception.'.class_basename($e),
            );
        }

        if ($result->successful && ($result->externalId === null || trim($result->externalId) === '')) {
            $result = PublishResult::failure(
                FailureClass::UnknownOutcome,
                'The channel may have accepted the publication but did not return a usable external id.',
                'publication.outcome_unknown.missing_external_id',
                $result->metadata,
            );
        }

        $safeResult = $this->redactPublishResult($result, $connection->credentials ?? []);

        DB::transaction(function () use ($log, $result, $safeResult, $publication): void {
            $this->logger->finishWithPublishResult($log, $result);

            if ($safeResult->successful && $safeResult->externalId !== null) {
                $publication->markPublished($safeResult->externalId, $safeResult->externalUrl);
                OmnichannelEventDispatcher::dispatch(new PublicationPublished($publication, $safeResult));

                return;
            }

            if ($safeResult->failureClass === FailureClass::UnknownOutcome) {
                $publication->markUnknownOutcome($safeResult->errorCode, $safeResult->errorMessage);
            } else {
                $publication->markFailed(
                    $safeResult->failureClass ?? FailureClass::Unknown,
                    $safeResult->errorCode,
                    $safeResult->errorMessage,
                );
            }

            OmnichannelEventDispatcher::dispatch(new PublicationFailed($publication, $safeResult));
        });

        return $safeResult;
    }

    public function schedule(Publication $publication, DateTimeInterface $scheduledAt): Publication
    {
        $this->assertPublicationAccess($publication);
        if ($scheduledAt->getTimestamp() <= now()->getTimestamp()) {
            throw new InvalidArgumentException('A publication can only be scheduled for a future time.');
        }

        $publication->loadMissing('channel');
        $capabilities = $this->channels->driverForPublication($publication)->capabilities();

        if (! $capabilities->supports(Capability::Scheduling)) {
            throw new UnsupportedCapabilityException(
                $publication->channel->driver,
                Capability::Scheduling,
                $capabilities->reasonFor(Capability::Scheduling),
            );
        }

        $publication->transitionTo(PublicationStatus::Scheduled, [
            'scheduled_at' => $scheduledAt,
            'failure_class' => null,
            'error_code' => null,
            'error_message' => null,
        ]);

        return $publication;
    }

    public function cancel(Publication $publication): Publication
    {
        $this->assertPublicationAccess($publication);
        $publication->transitionTo(PublicationStatus::Cancelled, [
            'scheduled_at' => null,
        ]);

        return $publication;
    }

    public function retry(Publication $publication, bool $confirmUnknownOutcome = false): Publication
    {
        $publication->refresh();
        $this->assertPublicationAccess($publication);

        if ($publication->status === PublicationStatus::UnknownOutcome) {
            if (! $confirmUnknownOutcome) {
                throw new RuntimeException('This publication may already exist on the channel. Review the attempt and explicitly confirm before sending again.');
            }

            $authorized = DB::transaction(function () use ($publication): bool {
                if (! $publication->authorizeUnknownOutcomeRetry()) {
                    return false;
                }

                $this->logger->recordManualReplayAuthorization($publication);

                return true;
            });

            if (! $authorized) {
                throw new RuntimeException('This publication outcome changed; refresh it and review the latest attempt before retrying.');
            }
        }

        if (! $publication->canBeRetried()) {
            throw new RuntimeException(
                "Publication #{$publication->id} is [{$publication->status->value}] and cannot be retried."
            );
        }

        $publication->forceFill([
            'failure_class' => null,
            'error_code' => null,
            'error_message' => null,
            'failed_at' => null,
        ])->save();

        PublishPublicationJob::dispatch($publication);

        return $publication;
    }

    public function update(Publication $publication, ?PublicationContent $content = null): PublishResult
    {
        $publication->refresh();
        $publication->loadMissing(['channel', 'connection']);
        $this->assertPublicationAccess($publication);

        $driver = $this->channels->driverForPublication($publication);
        $capabilities = $driver->capabilities();

        if (! $capabilities->supports(Capability::Update)) {
            return PublishResult::failure(
                FailureClass::Unsupported,
                $capabilities->reasonFor(Capability::Update)
                    ?? trans('omnichannel::omnichannel.errors.update_unsupported', ['channel' => $publication->channel->name]),
                'capability.update.unsupported',
            );
        }

        if (! $publication->isPublished() || $publication->external_id === null) {
            return PublishResult::failure(
                FailureClass::Validation,
                trans('omnichannel::omnichannel.errors.not_published_yet'),
                'publication.not_published',
            );
        }

        if (! $publication->connection->isUsable()) {
            return PublishResult::failure(
                FailureClass::Authentication,
                trans('omnichannel::omnichannel.errors.connection_not_usable', ['name' => $publication->connection->name]),
                'connection.not_usable',
            );
        }

        $confirmedContent = $publication->content();
        $proposedContent = $content ?? $confirmedContent;
        $candidate = clone $publication;
        $candidate->setContent($proposedContent);
        $changedFields = $this->changedContentFields($confirmedContent, $proposedContent);
        $unsupportedFields = array_values(array_filter(
            $changedFields,
            fn (string $field): bool => ! $capabilities->supportsUpdateField($field)
                || ($this->isEmptyUpdateValue($this->contentFieldValue($proposedContent, $field))
                    && ! $capabilities->canClearUpdateField($field)),
        ));

        if ($unsupportedFields !== []) {
            return PublishResult::failure(
                FailureClass::Unsupported,
                trans('omnichannel::omnichannel.errors.update_field_unsupported', [
                    'fields' => implode(', ', $unsupportedFields),
                ]),
                'publication.update.field_unsupported',
                ['unsupported_fields' => $unsupportedFields],
            );
        }

        $validation = $driver->validate($candidate);

        if ($validation->fails()) {
            $this->logger->recordValidationFailure($publication, $validation);

            return PublishResult::failure(
                FailureClass::Validation,
                $validation->summary(),
                $validation->errorCodes()[0] ?? 'validation.failed',
            );
        }

        $expectedContentVersion = $publication->content_version;
        if (! $publication->claimForExternalMutation(
            'update',
            $proposedContent->toArray(),
            expectedContentVersion: $expectedContentVersion,
        )) {
            $publication->refresh();
            $inFlight = $publication->attempt_token !== null;

            return PublishResult::failure(
                FailureClass::Transient,
                trans($inFlight
                    ? 'omnichannel::omnichannel.errors.operation_in_flight'
                    : 'omnichannel::omnichannel.errors.update_version_conflict'),
                $inFlight ? 'publication.operation_in_flight' : 'publication.update.version_conflict',
            );
        }

        $attemptToken = $publication->attempt_token;
        if ($attemptToken === null) {
            throw new RuntimeException('A successful update claim must have an attempt token.');
        }
        $candidate->forceFill([
            'attempt_token' => $attemptToken,
            'metadata' => $publication->metadata,
            'content_version' => $publication->content_version,
        ]);

        try {
            $log = $this->logger->start(
                LogOperation::Update,
                publication: $publication,
                connection: $publication->connection,
                request: [
                    'driver' => $driver->key(),
                    'external_id' => $publication->external_id,
                    'content_version' => $expectedContentVersion,
                    'updated_fields' => $changedFields,
                ],
            );
        } catch (Throwable $e) {
            $publication->finishContentUpdateFailure(
                $attemptToken,
                $proposedContent->toArray(),
                'failed',
                FailureClass::Transient->value,
                'publication.update.log_start_failed',
                app(SecretRedactor::class)->redactString($e->getMessage(), $publication->connection?->credentials ?? []),
            );

            throw $e;
        }

        try {
            $result = $this->apiRateLimitScope->using($publication->connection, fn () => $driver->update($candidate));
        } catch (Throwable $e) {
            $result = PublishResult::failure(
                FailureClass::UnknownOutcome,
                app(SecretRedactor::class)->redactString($e->getMessage(), $publication->connection?->credentials ?? []),
                'driver.exception.'.class_basename($e),
            );
        }

        $safeResult = $this->redactPublishResult($result, $publication->connection?->credentials ?? []);
        if ($safeResult->successful) {
            $safeResult = $safeResult->withMetadata(['updated_content_fields' => $changedFields]);
        }

        DB::transaction(function () use ($log, $result, $safeResult, $publication, $attemptToken, $proposedContent, $changedFields): void {
            $this->logger->finishWithPublishResult($log, $result);

            if ($safeResult->successful) {
                $publication->finishContentUpdate($attemptToken, $proposedContent, $changedFields, $safeResult->externalUrl);

                return;
            }

            $status = $safeResult->failureClass === FailureClass::UnknownOutcome
                ? 'unknown_outcome'
                : 'failed';

            $publication->finishContentUpdateFailure(
                $attemptToken,
                $proposedContent->toArray(),
                $status,
                $safeResult->failureClass?->value,
                $safeResult->errorCode,
                $safeResult->errorMessage,
            );
        });

        return $safeResult;
    }

    public function delete(Publication $publication): DeleteResult
    {
        $publication->refresh();
        $publication->loadMissing(['channel', 'connection']);
        $this->assertPublicationAccess($publication);

        $driver = $this->channels->driverForPublication($publication);
        $capabilities = $driver->capabilities();

        if (! $capabilities->supports(Capability::Delete)) {
            return DeleteResult::unsupported(
                $capabilities->reasonFor(Capability::Delete)
                    ?? trans('omnichannel::omnichannel.errors.delete_unsupported', ['channel' => $publication->channel->name])
            );
        }

        if (! $publication->isPublished() || $publication->external_id === null) {
            return DeleteResult::failure(
                FailureClass::Validation,
                trans('omnichannel::omnichannel.errors.not_published_yet'),
                'publication.not_published',
            );
        }

        if (! $publication->claimForExternalMutation('delete')) {
            return DeleteResult::failure(
                FailureClass::Transient,
                trans('omnichannel::omnichannel.errors.operation_in_flight'),
                'publication.operation_in_flight',
            );
        }
        $attemptToken = $publication->attempt_token;

        try {
            $log = $this->logger->start(
                LogOperation::Delete,
                publication: $publication,
                connection: $publication->connection,
                request: ['driver' => $driver->key(), 'external_id' => $publication->external_id],
            );
        } catch (Throwable $e) {
            $publication->releaseExternalMutation($attemptToken);

            throw $e;
        }

        try {
            $result = $this->apiRateLimitScope->using($publication->connection, fn () => $driver->delete($publication));
        } catch (Throwable $e) {
            $result = DeleteResult::failure(
                FailureClass::UnknownOutcome,
                app(SecretRedactor::class)->redactString($e->getMessage(), $publication->connection?->credentials ?? []),
                'driver.exception.'.class_basename($e),
            );
        }

        $safeResult = $this->redactDeleteResult($result, $publication->connection->credentials ?? []);

        DB::transaction(function () use ($log, $safeResult, $publication, $attemptToken): void {
            $this->logger->finishWithDeleteResult($log, $safeResult);

            if ($safeResult->successful) {
                $deletedExternalId = $safeResult->metadata['deleted_external_id']
                    ?? $safeResult->metadata['deleted_message_id']
                    ?? $safeResult->metadata['deleted_retailer_id']
                    ?? $publication->external_id;

                $publication->markDeleted([
                    'deleted_at' => now()->toIso8601String(),
                    'deleted_external_id' => $deletedExternalId,
                    'deleted_external_ids' => $safeResult->metadata['deleted_message_ids']
                        ?? $safeResult->metadata['deleted_external_ids']
                        ?? [$deletedExternalId],
                ]);
            } elseif ($safeResult->failureClass === FailureClass::UnknownOutcome) {
                $publication->markExternalMutationUnknown(
                    $attemptToken,
                    $safeResult->errorCode,
                    $safeResult->errorMessage,
                );
            } else {
                $publication->releaseExternalMutation($attemptToken);
            }
        });

        return $safeResult;
    }

    public function deactivate(Publication $publication): PublishResult
    {
        $publication->refresh();
        $publication->loadMissing(['channel', 'connection']);
        $this->assertPublicationAccess($publication);

        $driver = $this->channels->driverForPublication($publication);
        $capabilities = $driver->capabilities();
        if (! $capabilities->supports(Capability::Deactivate) || ! $driver instanceof DeactivatableChannelDriver) {
            return PublishResult::failure(
                FailureClass::Unsupported,
                $capabilities->reasonFor(Capability::Deactivate)
                    ?? trans('omnichannel::omnichannel.errors.deactivate_unsupported', ['channel' => $publication->channel->name]),
                'capability.deactivate.unsupported',
            );
        }

        if (! $publication->isPublished() || $publication->external_id === null) {
            return PublishResult::failure(
                FailureClass::Validation,
                trans('omnichannel::omnichannel.errors.not_published_yet'),
                'publication.not_published',
            );
        }

        if (! $publication->claimForExternalMutation('deactivate')) {
            return PublishResult::failure(
                FailureClass::Transient,
                trans('omnichannel::omnichannel.errors.operation_in_flight'),
                'publication.operation_in_flight',
            );
        }
        $attemptToken = $publication->attempt_token;

        try {
            $log = $this->logger->start(
                LogOperation::Deactivate,
                publication: $publication,
                connection: $publication->connection,
                request: ['driver' => $driver->key(), 'external_id' => $publication->external_id],
            );
        } catch (Throwable $e) {
            $publication->releaseExternalMutation($attemptToken);

            throw $e;
        }

        try {
            $result = $this->apiRateLimitScope->using($publication->connection, fn () => $driver->deactivate($publication));
        } catch (Throwable $e) {
            $result = PublishResult::failure(
                FailureClass::UnknownOutcome,
                app(SecretRedactor::class)->redactString($e->getMessage(), $publication->connection?->credentials ?? []),
                'driver.exception.'.class_basename($e),
            );
        }

        $safeResult = $this->redactPublishResult($result, $publication->connection->credentials ?? []);
        DB::transaction(function () use ($log, $safeResult, $publication, $attemptToken): void {
            $this->logger->finishWithPublishResult($log, $safeResult);

            if ($safeResult->successful) {
                $publication->finishExternalDeactivation($attemptToken, $safeResult->metadata);
            } elseif ($safeResult->failureClass === FailureClass::UnknownOutcome) {
                $publication->markExternalMutationUnknown($attemptToken, $safeResult->errorCode, $safeResult->errorMessage);
            } else {
                $publication->releaseExternalMutation($attemptToken);
            }
        });

        return $safeResult;
    }

    public function testConnection(ChannelConnection $connection): ConnectionTestResult
    {
        $this->context->assertBranchAccess($connection->branch_id);
        $connection->loadMissing('channel');
        $driver = $this->channels->driverForConnection($connection);

        $log = $this->logger->start(
            LogOperation::TestConnection,
            connection: $connection,
            request: ['driver' => $driver->key(), 'connection_id' => $connection->id],
        );

        try {
            $result = $this->apiRateLimitScope->using($connection, fn () => $driver->testConnection($connection));
        } catch (Throwable $e) {
            $result = ConnectionTestResult::failure(
                FailureClass::Transient,
                app(SecretRedactor::class)->redactString($e->getMessage(), $connection->credentials ?? []),
                'driver.exception.'.class_basename($e),
            );
        }

        $this->logger->finishWithConnectionTest($log, $result);

        if ($result->successful) {
            $wasActive = $connection->status === ConnectionStatus::Active;

            $connection->forceFill(array_filter([
                'status' => ConnectionStatus::Active,
                'status_message' => null,
                'last_tested_at' => now(),
                'external_account_id' => $result->accountId ?? $connection->external_account_id,
                'external_account_name' => $result->accountName ?? $connection->external_account_name,
                'expires_at' => $result->expiresAt ?? $connection->expires_at,
            ], static fn (mixed $v, string $k): bool => $k === 'status_message' || $v !== null, ARRAY_FILTER_USE_BOTH))->save();

            if (! $wasActive) {
                OmnichannelEventDispatcher::dispatch(new ConnectionConnected($connection));
            }
        } else {
            $status = match ($result->failureClass) {
                FailureClass::Authentication => ConnectionStatus::Expired,
                FailureClass::Permission => ConnectionStatus::Revoked,
                default => $connection->status,
            };

            $connection->forceFill([
                'status' => $status,
                'status_message' => app(SecretRedactor::class)->redactString((string) $result->errorMessage, $connection->credentials ?? []),
                'last_tested_at' => now(),
            ])->save();
        }

        return $result;
    }

    private function failWithoutAttempt(
        Publication $publication,
        FailureClass $class,
        string $code,
        string $message,
    ): PublishResult {
        $message = app(SecretRedactor::class)->redactString($message, $publication->connection?->credentials ?? []);
        $publication->refresh();

        if (in_array($publication->status, [PublicationStatus::Draft, PublicationStatus::Scheduled], true)) {
            $publication->markFailed($class, $code, $message);
        } elseif ($publication->status === PublicationStatus::Failed) {
            $publication->updatePreflightFailure($class, $code, $message);
        }

        return $this->redactPublishResult(PublishResult::failure($class, $message, $code), $publication->connection?->credentials ?? []);
    }

    /** @return list<string> */
    private function changedContentFields(PublicationContent $confirmed, PublicationContent $proposed): array
    {
        $confirmedArray = $confirmed->toArray();
        $proposedArray = $proposed->toArray();
        $changed = [];

        foreach (['caption', 'hashtags', 'title'] as $field) {
            if (($confirmedArray[$field] ?? null) !== ($proposedArray[$field] ?? null)) {
                $changed[] = $field;
            }
        }

        foreach (['image', 'video'] as $mediaType) {
            $oldMedia = array_values(array_map(
                static fn ($item): array => $item->toArray(),
                array_filter($confirmed->media, static fn ($item): bool => $item->type->value === $mediaType),
            ));
            $newMedia = array_values(array_map(
                static fn ($item): array => $item->toArray(),
                array_filter($proposed->media, static fn ($item): bool => $item->type->value === $mediaType),
            ));

            if ($oldMedia !== $newMedia) {
                $changed[] = 'media.'.$mediaType;
            }
        }

        $oldExtra = $confirmedArray['extra'] ?? [];
        $newExtra = $proposedArray['extra'] ?? [];
        foreach (array_unique([...array_keys($oldExtra), ...array_keys($newExtra)]) as $key) {
            if (($oldExtra[$key] ?? null) !== ($newExtra[$key] ?? null)) {
                $changed[] = 'extra.'.$key;
            }
        }

        return $changed;
    }

    private function contentFieldValue(PublicationContent $content, string $field): mixed
    {
        return match ($field) {
            'caption' => $content->caption,
            'hashtags' => $content->hashtags,
            'title' => $content->title,
            'media.image' => array_map(
                static fn ($item): array => $item->toArray(),
                array_filter($content->media, static fn ($item): bool => $item->type->value === 'image'),
            ),
            'media.video' => array_map(
                static fn ($item): array => $item->toArray(),
                array_filter($content->media, static fn ($item): bool => $item->type->value === 'video'),
            ),
            default => str_starts_with($field, 'extra.')
                ? data_get($content->extra, substr($field, 6))
                : null,
        };
    }

    private function isEmptyUpdateValue(mixed $value): bool
    {
        return $value === null
            || $value === []
            || (is_string($value) && trim($value) === '');
    }

    private function redactPublishResult(PublishResult $result, array $secrets = []): PublishResult
    {
        $redactor = app(SecretRedactor::class);
        $metadata = $redactor->redactAndTrim($result->metadata, 8192, $secrets);

        return $result->successful
            ? PublishResult::success(
                $redactor->redactString($result->externalId ?? '', $secrets),
                $result->externalUrl !== null ? $redactor->redactString($result->externalUrl, $secrets) : null,
                $metadata,
            )
            : PublishResult::failure(
                $result->failureClass ?? FailureClass::Unknown,
                $redactor->redactString($result->errorMessage ?? '', $secrets),
                $result->errorCode !== null ? $redactor->redactString($result->errorCode, $secrets) : null,
                $metadata,
            );
    }

    private function assertPublicationAccess(Publication $publication): void
    {
        $publication->refresh();
        $publication->load(['connection', 'channel']);
        $this->context->assertBranchAccess($publication->branch_id);
        $publication->loadMissing('connection');
        $connectionBranchId = $publication->connection?->branch_id;
        $this->context->assertBranchAccess($connectionBranchId);
        if ((string) $publication->branch_id !== (string) $connectionBranchId
            || (string) $publication->channel_id !== (string) $publication->connection?->channel_id) {
            throw new BranchAccessDeniedException;
        }
    }

    private function redactDeleteResult(DeleteResult $result, array $secrets = []): DeleteResult
    {
        $redactor = app(SecretRedactor::class);
        $metadata = $redactor->redactAndTrim($result->metadata, 8192, $secrets);

        return $result->successful
            ? DeleteResult::success($metadata)
            : DeleteResult::failure(
                $result->failureClass ?? FailureClass::Unknown,
                $redactor->redactString($result->errorMessage ?? '', $secrets),
                $result->errorCode !== null ? $redactor->redactString($result->errorCode, $secrets) : null,
                $metadata,
            );
    }
}

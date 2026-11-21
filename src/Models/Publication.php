<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Models;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Karnoweb\Omnichannel\Database\Factories\PublicationFactory;
use Karnoweb\Omnichannel\Enums\FailureClass;
use Karnoweb\Omnichannel\Enums\PublicationSourceType;
use Karnoweb\Omnichannel\Enums\PublicationStatus;
use Karnoweb\Omnichannel\Enums\PublicationType;
use Karnoweb\Omnichannel\Exceptions\InvalidStatusTransitionException;
use Karnoweb\Omnichannel\Exceptions\StalePublicationTransitionException;
use Karnoweb\Omnichannel\Omnichannel;
use Karnoweb\Omnichannel\Services\SecretRedactor;
use Karnoweb\Omnichannel\Support\BranchScope;
use Karnoweb\Omnichannel\Support\OmnichannelTables;
use Karnoweb\Omnichannel\ValueObjects\PublicationContent;

/**
 * @property int $id
 * @property int|string|null $branch_id
 * @property int|string|null $created_by
 * @property string|null            $product_id
 * @property PublicationSourceType  $source_type
 * @property string|null $product_name
 * @property int $channel_id
 * @property int $channel_connection_id
 * @property PublicationType $type
 * @property PublicationStatus $status
 * @property array<string, mixed> $content
 * @property CarbonImmutable|null $scheduled_at
 * @property CarbonImmutable|null $published_at
 * @property CarbonImmutable|null $failed_at
 * @property string|null $external_id
 * @property string|null $external_url
 * @property string $idempotency_key
 * @property FailureClass|null $failure_class
 * @property string|null $error_code
 * @property string|null $error_message
 * @property int $attempts
 * @property string|null $attempt_token
 * @property int $content_version
 * @property CarbonImmutable|null $last_attempted_at
 * @property array<string, mixed>|null $metadata
 * @property-read Channel              $channel
 * @property-read ChannelConnection    $connection
 */
class Publication extends BaseModel
{
    use BranchScope;

    /** @use HasFactory<PublicationFactory> */
    use HasFactory;

    protected $table = 'publications';

    protected $hidden = ['attempt_token'];

    protected $fillable = [
        'branch_id',
        'created_by',
        'source_type',
        'source_reference_type',
        'source_reference_id',
        'source_title',
        'product_id',
        'product_name',
        'channel_id',
        'channel_connection_id',
        'type',
        'status',
        'content',
        'scheduled_at',
        'published_at',
        'failed_at',
        'external_id',
        'external_url',
        'idempotency_key',
        'failure_class',
        'error_code',
        'error_message',
        'attempts',
        'attempt_token',
        'content_version',
        'last_attempted_at',
        'metadata',
    ];

    public function toArray(): array
    {
        $data = parent::toArray();
        $secrets = $this->connection?->credentials ?? [];
        $redactor = app(SecretRedactor::class);
        foreach (['error_message', 'external_url'] as $field) {
            if (is_string($data[$field] ?? null)) {
                $data[$field] = $redactor->redactString($data[$field], $secrets);
            }
        }
        foreach (['content', 'metadata'] as $field) {
            if (is_array($data[$field] ?? null)) {
                $data[$field] = $redactor->redact($data[$field], 0, $secrets);
            }
        }

        return $data;
    }

    /**
     * @return BelongsTo<Channel, $this>
     */
    public function channel(): BelongsTo
    {
        /** @var class-string<Channel> $related */
        $related = Omnichannel::model('channel');

        return $this->belongsTo($related, 'channel_id');
    }

    /**
     * @return BelongsTo<ChannelConnection, $this>
     */
    public function connection(): BelongsTo
    {
        /** @var class-string<ChannelConnection> $related */
        $related = Omnichannel::model('connection');

        return $this->belongsTo($related, 'channel_connection_id');
    }

    /**
     * @return BelongsTo<Model, $this>|null
     */
    public function creator(): ?BelongsTo
    {
        $userClass = Omnichannel::optionalModel('user');

        return $userClass !== null ? $this->belongsTo($userClass, 'created_by') : null;
    }

    /**
     * @return BelongsTo<Model, $this>|null
     */
    public function branch(): ?BelongsTo
    {
        $branchClass = Omnichannel::optionalModel('branch');

        return $branchClass !== null ? $this->belongsTo($branchClass, 'branch_id') : null;
    }

    /**
     * @return HasMany<PublicationLog, $this>
     */
    public function mediaAssets(): BelongsToMany
    {
        return $this->belongsToMany(
            PublicationMediaAsset::class,
            OmnichannelTables::name('publication_media'),
            'publication_id',
            'media_asset_id',
        )->withPivot(['sort_order', 'role'])->orderByPivot('sort_order');
    }

    public function logs(): HasMany
    {
        /** @var class-string<PublicationLog> $related */
        $related = Omnichannel::model('publication_log');

        return $this->hasMany($related, 'publication_id')->latest('id');
    }

    public function content(): PublicationContent
    {
        return PublicationContent::fromArray($this->content ?? []);
    }

    public function setContent(PublicationContent $content): void
    {
        $this->content = $content->toArray();
    }

    public function persistentArtifact(string $operation, string $slot): mixed
    {
        $metadata = static::query()->whereKey($this->getKey())->value('metadata');
        if (is_string($metadata)) {
            $metadata = json_decode($metadata, true);
        }

        $key = hash('sha256', $this->idempotency_key.'|'.$operation.'|'.$slot);

        return is_array($metadata) ? data_get($metadata, 'omnichannel.artifacts.'.$key) : null;
    }

    public function rememberPersistentArtifact(string $operation, string $slot, mixed $value): void
    {
        $metadata = static::query()->whereKey($this->getKey())->value('metadata');
        if (is_string($metadata)) {
            $metadata = json_decode($metadata, true);
        }

        $metadata = is_array($metadata) ? $metadata : [];
        $key = hash('sha256', $this->idempotency_key.'|'.$operation.'|'.$slot);
        data_set($metadata, 'omnichannel.artifacts.'.$key, $value);

        $this->forceFill(['metadata' => $metadata])->save();
    }

    public function forgetPersistentArtifact(string $operation, string $slot): void
    {
        $metadata = static::query()->whereKey($this->getKey())->value('metadata');
        if (is_string($metadata)) {
            $metadata = json_decode($metadata, true);
        }

        if (! is_array($metadata)) {
            return;
        }

        $key = hash('sha256', $this->idempotency_key.'|'.$operation.'|'.$slot);
        data_forget($metadata, 'omnichannel.artifacts.'.$key);
        $this->forceFill(['metadata' => $metadata])->save();
    }

    public function canTransitionTo(PublicationStatus $target): bool
    {
        return $this->status->canTransitionTo($target);
    }

    /**
     * @param  array<string, mixed>  $attributes
     *
     * @throws InvalidStatusTransitionException
     */
    public function transitionTo(PublicationStatus $target, array $attributes = []): void
    {
        if (! $this->canTransitionTo($target)) {
            throw new InvalidStatusTransitionException($this->status, $target, $this->id);
        }

        $expectedStatus = $this->status;
        $expectedAttemptToken = $this->getRawOriginal('attempt_token');
        $candidate = clone $this;
        $candidate->forceFill([...$attributes, 'status' => $target]);
        if ($target !== PublicationStatus::Publishing) {
            $candidate->setAttribute('attempt_token', null);
        }

        $updates = $candidate->getDirty();
        $updates['updated_at'] = now();

        $query = static::query()
            ->whereKey($this->getKey())
            ->where('status', $expectedStatus->value);

        $expectedAttemptToken === null
            ? $query->whereNull('attempt_token')
            : $query->where('attempt_token', $expectedAttemptToken);

        if ($query->update($updates) !== 1) {
            $this->refresh();
            throw new StalePublicationTransitionException($this->getKey());
        }

        $this->refresh();
    }

    public function claimForPublishing(?string $attemptToken = null): bool
    {
        $claimable = [
            PublicationStatus::Draft->value,
            PublicationStatus::Scheduled->value,
            PublicationStatus::Failed->value,
        ];

        $attemptToken = $attemptToken !== null && $attemptToken !== ''
            ? $attemptToken
            : Str::uuid()->toString();
        $claimed = static::query()
            ->whereKey($this->getKey())
            ->whereIn('status', $claimable)
            ->where('status', $this->getRawOriginal('status'))
            ->where('scheduled_at', $this->getRawOriginal('scheduled_at'))
            ->where(function ($query): void {
                $query->where('status', '!=', PublicationStatus::Scheduled->value)
                    ->orWhere('scheduled_at', '<=', now());
            })
            ->update([
                'status' => PublicationStatus::Publishing->value,
                'attempts' => DB::raw('attempts + 1'),
                'attempt_token' => $attemptToken,
                'last_attempted_at' => now(),
                'updated_at' => now(),
            ]);

        if ($claimed === 0) {
            return false;
        }

        $this->refresh();

        return true;
    }

    /**
     * Claim the shared mutation lock for an update or delete without holding a DB transaction over the network call.
     *
     * @param  array<string, mixed>  $pendingContent
     */
    public function claimForExternalMutation(
        string $operation,
        array $pendingContent = [],
        ?string $attemptToken = null,
        ?int $expectedContentVersion = null,
    ): bool {
        $attemptToken = $attemptToken !== null && $attemptToken !== ''
            ? $attemptToken
            : Str::uuid()->toString();
        $metadata = $this->metadata ?? [];
        $startedAt = now()->toIso8601String();
        data_set($metadata, 'omnichannel.active_operation', [
            'type' => $operation,
            'started_at' => $startedAt,
        ]);

        if ($operation === 'update') {
            data_set($metadata, 'omnichannel.content_sync.status', 'updating');
            data_set($metadata, 'omnichannel.content_sync.pending_content', $pendingContent);
            data_set($metadata, 'omnichannel.content_sync.started_at', $startedAt);
        }

        $query = static::query()
            ->whereKey($this->getKey())
            ->where('status', PublicationStatus::Published->value)
            ->whereNull('attempt_token');

        if ($expectedContentVersion !== null) {
            $query->where('content_version', $expectedContentVersion);
        }

        $claimed = $query->update([
            'attempt_token' => $attemptToken,
            'metadata' => json_encode($metadata, JSON_THROW_ON_ERROR),
            'updated_at' => now(),
        ]);

        if ($claimed === 0) {
            return false;
        }

        $this->refresh();

        return true;
    }

    /** @param list<string> $updatedFields */
    public function finishContentUpdate(
        string $attemptToken,
        PublicationContent $content,
        array $updatedFields,
        ?string $externalUrl = null,
    ): void {
        $metadata = $this->metadata ?? [];
        $contentVersion = $this->content_version + 1;
        data_forget($metadata, 'omnichannel.active_operation');
        data_set($metadata, 'omnichannel.content_sync', [
            'status' => 'synced',
            'content_version' => $contentVersion,
            'updated_fields' => array_values($updatedFields),
            'last_synced_at' => now()->toIso8601String(),
        ]);

        $this->persistOwnedMutation($attemptToken, [
            'content' => $content->toArray(),
            'content_version' => $contentVersion,
            'external_url' => $externalUrl ?? $this->external_url,
            'failure_class' => null,
            'error_code' => null,
            'error_message' => null,
            'metadata' => $metadata,
        ]);
    }

    /** @param array<string, mixed> $pendingContent */
    public function finishContentUpdateFailure(
        string $attemptToken,
        array $pendingContent,
        string $status,
        ?string $failureClass,
        ?string $errorCode,
        ?string $errorMessage,
    ): void {
        $metadata = $this->metadata ?? [];
        if ($status !== 'unknown_outcome') {
            data_forget($metadata, 'omnichannel.active_operation');
        }
        data_set($metadata, 'omnichannel.content_sync.status', $status);
        data_set($metadata, 'omnichannel.content_sync.pending_content', $pendingContent);
        data_set($metadata, 'omnichannel.content_sync.error_code', $errorCode);
        data_set($metadata, 'omnichannel.content_sync.error_message', $errorMessage);
        data_set($metadata, 'omnichannel.content_sync.finished_at', now()->toIso8601String());

        $this->persistOwnedMutation($attemptToken, [
            'failure_class' => $failureClass,
            'error_code' => $errorCode,
            'error_message' => $errorMessage,
            'metadata' => $metadata,
        ]);
    }

    public function releaseExternalMutation(string $attemptToken): void
    {
        $metadata = $this->metadata ?? [];
        data_forget($metadata, 'omnichannel.active_operation');

        $this->persistOwnedMutation($attemptToken, ['metadata' => $metadata]);
    }

    /** @param array<string, mixed> $resultMetadata */
    public function finishExternalDeactivation(string $attemptToken, array $resultMetadata = []): void
    {
        $metadata = $this->metadata ?? [];
        data_set($metadata, 'omnichannel.deactivation', [
            'status' => 'deactivated',
            'deactivated_at' => now()->toIso8601String(),
            'result' => $resultMetadata,
        ]);
        data_set($metadata, 'omnichannel.active_operation.status', 'succeeded');
        data_set($metadata, 'omnichannel.active_operation.finished_at', now()->toIso8601String());

        $updated = static::query()
            ->whereKey($this->getKey())
            ->where('status', PublicationStatus::Published->value)
            ->where('attempt_token', $attemptToken)
            ->update([
                'attempt_token' => null,
                'metadata' => json_encode($metadata, JSON_THROW_ON_ERROR),
                'updated_at' => now(),
            ]);

        if ($updated !== 1) {
            $this->refresh();
            throw new StalePublicationTransitionException($this->getKey());
        }

        $this->refresh();
    }

    public function markExternalMutationUnknown(
        string $attemptToken,
        ?string $errorCode,
        ?string $errorMessage,
    ): void {
        $metadata = $this->metadata ?? [];
        data_set($metadata, 'omnichannel.active_operation.status', 'unknown_outcome');
        data_set($metadata, 'omnichannel.active_operation.error_code', $errorCode);
        data_set($metadata, 'omnichannel.active_operation.error_message', $errorMessage);
        data_set($metadata, 'omnichannel.active_operation.finished_at', now()->toIso8601String());

        $candidate = clone $this;
        $candidate->forceFill(['metadata' => $metadata]);
        $updates = $candidate->getDirty();
        $updates['updated_at'] = now();

        $updated = static::query()
            ->whereKey($this->getKey())
            ->where('status', PublicationStatus::Published->value)
            ->where('attempt_token', $attemptToken)
            ->update($updates);

        if ($updated !== 1) {
            $this->refresh();
            throw new StalePublicationTransitionException($this->getKey());
        }

        $this->refresh();
    }

    /** @param array<string, mixed> $attributes */
    private function persistOwnedMutation(string $attemptToken, array $attributes): void
    {
        $candidate = clone $this;
        $retainOwnership = data_get($attributes, 'metadata.omnichannel.content_sync.status') === 'unknown_outcome';
        $candidate->forceFill([...$attributes, 'attempt_token' => $retainOwnership ? $attemptToken : null]);
        $updates = $candidate->getDirty();
        $updates['updated_at'] = now();

        $updated = static::query()
            ->whereKey($this->getKey())
            ->where('status', PublicationStatus::Published->value)
            ->where('attempt_token', $attemptToken)
            ->update($updates);

        if ($updated !== 1) {
            $this->refresh();
            throw new StalePublicationTransitionException($this->getKey());
        }

        $this->refresh();
    }

    public function markPublished(string $externalId, ?string $externalUrl = null): void
    {
        $this->transitionTo(PublicationStatus::Published, [
            'external_id' => $externalId,
            'external_url' => $externalUrl,
            'published_at' => now(),
            'failed_at' => null,
            'failure_class' => null,
            'error_code' => null,
            'error_message' => null,
        ]);
    }

    public function markFailed(FailureClass $class, ?string $code, ?string $message): void
    {
        $message = $message !== null
            ? app(SecretRedactor::class)
                ->redactString($message, $this->connection?->credentials ?? [])
            : null;
        $this->transitionTo(PublicationStatus::Failed, [
            'failed_at' => now(),
            'failure_class' => $class,
            'error_code' => $code,
            'error_message' => $message,
        ]);
    }

    public function updatePreflightFailure(FailureClass $class, ?string $code, ?string $message): bool
    {
        $message = $message !== null
            ? app(SecretRedactor::class)
                ->redactString($message, $this->connection?->credentials ?? [])
            : null;

        $updated = static::query()
            ->whereKey($this->getKey())
            ->where('status', PublicationStatus::Failed->value)
            ->whereNull('attempt_token')
            ->update([
                'failed_at' => now(),
                'failure_class' => $class->value,
                'error_code' => $code,
                'error_message' => $message,
                'updated_at' => now(),
            ]);

        if ($updated === 1) {
            $this->refresh();
        }

        return $updated === 1;
    }

    public function markUnknownOutcome(?string $code, ?string $message): void
    {
        $message = $message !== null
            ? app(SecretRedactor::class)->redactString($message, $this->connection?->credentials ?? [])
            : null;

        $this->transitionTo(PublicationStatus::UnknownOutcome, [
            'failed_at' => now(),
            'failure_class' => FailureClass::UnknownOutcome,
            'error_code' => $code,
            'error_message' => $message,
        ]);
    }

    public function authorizeUnknownOutcomeRetry(): bool
    {
        $updated = static::query()
            ->whereKey($this->getKey())
            ->where('status', PublicationStatus::UnknownOutcome->value)
            ->update([
                'status' => PublicationStatus::Failed->value,
                'attempt_token' => null,
                'updated_at' => now(),
            ]);

        if ($updated === 0) {
            return false;
        }

        $this->refresh();

        return true;
    }

    public function isPublished(): bool
    {
        return $this->status === PublicationStatus::Published;
    }

    public function isDue(): bool
    {
        return $this->status === PublicationStatus::Scheduled
            && $this->scheduled_at !== null
            && $this->scheduled_at->lte(now());
    }

    public function markDeleted(array $metadata = []): void
    {
        $this->transitionTo(PublicationStatus::Deleted, [
            'external_id' => null,
            'external_url' => null,
            'published_at' => null,
            'metadata' => [...($this->metadata ?? []), ...$metadata],
        ]);
    }

    public function canBeRetried(): bool
    {
        return $this->status === PublicationStatus::Failed;
    }

    public function canBeCancelled(): bool
    {
        return $this->status->canTransitionTo(PublicationStatus::Cancelled);
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopeDue(Builder $query): void
    {
        $query->where('status', PublicationStatus::Scheduled->value)
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now());
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopeStuckInFlight(Builder $query, DateTimeInterface $before): void
    {
        $query->where(function (Builder $query) use ($before): void {
            $query->where(function (Builder $query) use ($before): void {
                $query->where('status', PublicationStatus::Publishing->value)
                    ->where(function (Builder $query) use ($before): void {
                        $query->whereNull('last_attempted_at')
                            ->orWhere('last_attempted_at', '<=', $before);
                    });
            })->orWhere(function (Builder $query) use ($before): void {
                $query->where('status', PublicationStatus::Published->value)
                    ->whereNotNull('attempt_token')
                    ->where('updated_at', '<=', $before);
            });
        });
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopeWithStatus(Builder $query, PublicationStatus ...$statuses): void
    {
        $query->whereIn('status', array_map(
            static fn (PublicationStatus $s): string => $s->value,
            $statuses
        ));
    }

    protected static function newFactory(): PublicationFactory
    {
        return PublicationFactory::new();
    }

    protected function casts(): array
    {
        return [
            'source_type' => PublicationSourceType::class,
            'type' => PublicationType::class,
            'status' => PublicationStatus::class,
            'content' => 'array',
            'metadata' => 'array',
            'failure_class' => FailureClass::class,
            'attempts' => 'integer',
            'content_version' => 'integer',
            'scheduled_at' => 'immutable_datetime',
            'published_at' => 'immutable_datetime',
            'failed_at' => 'immutable_datetime',
            'last_attempted_at' => 'immutable_datetime',
        ];
    }
}

<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Karnoweb\Omnichannel\Database\Factories\PublicationLogFactory;
use Karnoweb\Omnichannel\Enums\FailureClass;
use Karnoweb\Omnichannel\Enums\LogOperation;
use Karnoweb\Omnichannel\Enums\LogStatus;
use Karnoweb\Omnichannel\Omnichannel;
use Karnoweb\Omnichannel\Services\SecretRedactor;

/**
 * @property int $id
 * @property int|null $publication_id
 * @property int|null $channel_connection_id
 * @property LogOperation $operation
 * @property LogStatus $status
 * @property int $attempt
 * @property CarbonImmutable|null $started_at
 * @property CarbonImmutable|null $finished_at
 * @property int|null $duration_ms
 * @property string|null $external_id
 * @property FailureClass|null $failure_class
 * @property string|null $error_code
 * @property string|null $error_message
 * @property bool $retryable
 * @property array<string, mixed>|null $request
 * @property array<string, mixed>|null $response
 * @property array<string, mixed>|null $context
 */
class PublicationLog extends BaseModel
{
    /** @use HasFactory<PublicationLogFactory> */
    use HasFactory;

    protected $table = 'publication_logs';

    protected $fillable = [
        'publication_id',
        'channel_connection_id',
        'operation',
        'status',
        'attempt',
        'started_at',
        'finished_at',
        'duration_ms',
        'external_id',
        'failure_class',
        'error_code',
        'error_message',
        'retryable',
        'request',
        'response',
        'context',
    ];

    public function toArray(): array
    {
        $data = parent::toArray();
        $redactor = app(SecretRedactor::class);
        $secrets = $this->connection?->credentials ?? [];
        foreach (['error_message'] as $field) {
            if (is_string($data[$field] ?? null)) {
                $data[$field] = $redactor->redactString($data[$field], $secrets);
            }
        }
        foreach (['request', 'response', 'context'] as $field) {
            if (is_array($data[$field] ?? null)) {
                $data[$field] = $redactor->redact($data[$field], 0, $secrets);
            }
        }

        return $data;
    }

    /**
     * @return BelongsTo<Publication, $this>
     */
    public function publication(): BelongsTo
    {
        /** @var class-string<Publication> $related */
        $related = Omnichannel::model('publication');

        return $this->belongsTo($related, 'publication_id');
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

    public function succeeded(): bool
    {
        return $this->status === LogStatus::Succeeded;
    }

    public function durationForHumans(): ?string
    {
        if ($this->duration_ms === null) {
            return null;
        }

        return $this->duration_ms < 1000
            ? $this->duration_ms.' ms'
            : number_format($this->duration_ms / 1000, 2).' s';
    }

    protected static function newFactory(): PublicationLogFactory
    {
        return PublicationLogFactory::new();
    }

    protected function casts(): array
    {
        return [
            'operation' => LogOperation::class,
            'status' => LogStatus::class,
            'failure_class' => FailureClass::class,
            'attempt' => 'integer',
            'duration_ms' => 'integer',
            'retryable' => 'boolean',
            'request' => 'array',
            'response' => 'array',
            'context' => 'array',
            'started_at' => 'immutable_datetime',
            'finished_at' => 'immutable_datetime',
        ];
    }
}

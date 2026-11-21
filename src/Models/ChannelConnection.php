<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Karnoweb\Omnichannel\Database\Factories\ChannelConnectionFactory;
use Karnoweb\Omnichannel\Enums\ConnectionStatus;
use Karnoweb\Omnichannel\Omnichannel;
use Karnoweb\Omnichannel\Services\SecretRedactor;
use Karnoweb\Omnichannel\Support\BranchScope;

/**
 * @property int $id
 * @property int|string|null $branch_id
 * @property int|string|null $created_by
 * @property int $channel_id
 * @property string $name
 * @property string|null $external_account_id
 * @property string|null $external_account_name
 * @property array<string, mixed>|null $credentials
 * @property array<string, mixed>|null $metadata
 * @property ConnectionStatus $status
 * @property string|null $status_message
 * @property CarbonImmutable|null $connected_at
 * @property CarbonImmutable|null $expires_at
 * @property CarbonImmutable|null $last_tested_at
 * @property-read Channel              $channel
 */
class ChannelConnection extends BaseModel
{
    use BranchScope;

    /** @use HasFactory<ChannelConnectionFactory> */
    use HasFactory;

    protected $table = 'connections';

    protected $fillable = [
        'branch_id',
        'created_by',
        'channel_id',
        'name',
        'external_account_id',
        'external_account_name',
        'credentials',
        'metadata',
        'status',
        'status_message',
        'connected_at',
        'expires_at',
        'last_tested_at',
    ];

    /** @var list<string> */
    protected $hidden = ['credentials'];

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
     * @return HasMany<Publication, $this>
     */
    public function publications(): HasMany
    {
        /** @var class-string<Publication> $related */
        $related = Omnichannel::model('publication');

        return $this->hasMany($related, 'channel_connection_id');
    }

    /**
     * @return HasMany<PublicationLog, $this>
     */
    public function logs(): HasMany
    {
        /** @var class-string<PublicationLog> $related */
        $related = Omnichannel::model('publication_log');

        return $this->hasMany($related, 'channel_connection_id');
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', ConnectionStatus::Active->value);
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopeExpiringWithin(Builder $query, int $days): void
    {
        $query->whereNotNull('expires_at')
            ->where('expires_at', '<=', now()->addDays($days));
    }

    public function credential(string $key, mixed $default = null): mixed
    {
        return data_get($this->credentials, $key, $default);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function mergeCredentials(array $values): void
    {
        $this->credentials = [...($this->credentials ?? []), ...$values];
    }

    public function maskedCredential(string $key): ?string
    {
        $value = $this->credential($key);

        if (! is_string($value) || $value === '') {
            return null;
        }

        return mb_strlen($value) <= 8
            ? str_repeat('•', mb_strlen($value))
            : str_repeat('•', 8).' '.mb_substr($value, -4);
    }

    public function toArray(): array
    {
        $data = parent::toArray();

        $secrets = $this->credentials ?? [];
        foreach (['status_message', 'external_account_name'] as $attribute) {
            if (is_string($data[$attribute] ?? null)) {
                $data[$attribute] = app(SecretRedactor::class)
                    ->redactString($data[$attribute], $secrets);
            }
        }

        if (is_array($data['metadata'] ?? null)) {
            $data['metadata'] = app(SecretRedactor::class)
                ->redact($data['metadata'], 0, $secrets);
        }

        return $data;
    }

    public function primaryMaskedSecret(): ?string
    {
        foreach (['access_token', 'bot_token', 'gateway_token'] as $key) {
            $masked = $this->maskedCredential($key);
            if ($masked !== null) {
                return $masked;
            }
        }

        return null;
    }

    public function isUsable(): bool
    {
        return $this->status->isUsable() && ! $this->hasExpired();
    }

    public function hasExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function expiresWithinDays(int $days): bool
    {
        return $this->expires_at !== null && $this->expires_at->lte(now()->addDays($days));
    }

    public function markTested(bool $successful, ?string $message = null): void
    {
        $this->forceFill([
            'last_tested_at' => now(),
            'status' => $successful ? ConnectionStatus::Active : $this->status,
            'status_message' => $message,
        ])->save();
    }

    protected static function newFactory(): ChannelConnectionFactory
    {
        return ChannelConnectionFactory::new();
    }

    protected function casts(): array
    {
        return [
            'credentials' => 'encrypted:array',
            'metadata' => 'array',
            'status' => ConnectionStatus::class,
            'connected_at' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime',
            'last_tested_at' => 'immutable_datetime',
        ];
    }
}

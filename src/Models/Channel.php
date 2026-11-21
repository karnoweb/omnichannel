<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Karnoweb\Omnichannel\Database\Factories\ChannelFactory;
use Karnoweb\Omnichannel\Database\Seeders\ChannelSeeder;
use Karnoweb\Omnichannel\Omnichannel;

/**
 * @property int                       $id
 * @property string                    $name
 * @property string                    $slug
 * @property string                    $driver
 * @property bool                      $enabled
 * @property int                       $sort_order
 * @property array<string, mixed>|null $metadata
 */
class Channel extends BaseModel
{
    /** @use HasFactory<ChannelFactory> */
    use HasFactory;

    protected $table = 'channels';

    protected $fillable = [
        'name',
        'slug',
        'driver',
        'enabled',
        'sort_order',
        'metadata',
    ];

    public static function ensureDefaultsSeeded(): void
    {
        /** @var class-string<self> $modelClass */
        $modelClass = Omnichannel::model('channel');

        if ($modelClass::query()->doesntExist()) {
            ChannelSeeder::syncChannels();
        }
    }

    /**
     * @return HasMany<ChannelConnection, $this>
     */
    public function connections(): HasMany
    {
        /** @var class-string<ChannelConnection> $related */
        $related = Omnichannel::model('connection');

        return $this->hasMany($related, 'channel_id');
    }

    /**
     * @return HasMany<Publication, $this>
     */
    public function publications(): HasMany
    {
        /** @var class-string<Publication> $related */
        $related = Omnichannel::model('publication');

        return $this->hasMany($related, 'channel_id');
    }

    /**
     * @param Builder<static> $query
     */
    public function scopeEnabled(Builder $query): void
    {
        $query->where('enabled', true);
    }

    public function config(?string $key = null, mixed $default = null): mixed
    {
        $path = 'omnichannel.channels.' . $this->driver;

        return config($key === null ? $path : $path . '.' . $key, $default);
    }

    protected static function newFactory(): ChannelFactory
    {
        return ChannelFactory::new();
    }

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'sort_order' => 'integer',
            'metadata' => 'array',
        ];
    }
}

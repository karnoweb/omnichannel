<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Karnoweb\Omnichannel\ValueObjects\PublicationContent;

/**
 * @implements CastsAttributes<array<string, mixed>|null, array<string, mixed>|null>
 */
final class PublicationMetadataCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?array
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        if (! is_array($value)) {
            return [];
        }

        self::normalizeNestedContent($value);

        return $value;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if (! is_array($value)) {
            $value = [];
        }

        self::normalizeNestedContent($value);

        return json_encode($value, JSON_THROW_ON_ERROR);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function normalizeNestedContent(array &$data): void
    {
        $pending = data_get($data, 'omnichannel.content_sync.pending_content');

        if (is_array($pending)) {
            data_set(
                $data,
                'omnichannel.content_sync.pending_content',
                PublicationContent::fromArray($pending)->toArray(),
            );
        }
    }
}

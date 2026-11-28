<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Karnoweb\Omnichannel\ValueObjects\PublicationContent;

/**
 * Canonical key order for content JSON (MySQL may reorder keys on read).
 *
 * @implements CastsAttributes<array<string, mixed>|null, array<string, mixed>|null>
 */
final class PublicationContentCast implements CastsAttributes
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

        return PublicationContent::fromArray($value)->toArray();
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if (! is_array($value)) {
            $value = [];
        }

        return json_encode(
            PublicationContent::fromArray($value)->toArray(),
            JSON_THROW_ON_ERROR,
        );
    }
}

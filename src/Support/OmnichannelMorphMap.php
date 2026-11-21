<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Support;

use Illuminate\Database\Eloquent\Relations\Relation;

final class OmnichannelMorphMap
{
    public static function register(): void
    {
        /** @var array<string, class-string> $map */
        $map = (array) config('omnichannel.morph_map', []);
        $valid = array_filter($map, static fn (mixed $class): bool => is_string($class) && class_exists($class));

        if ($valid === []) {
            return;
        }

        Relation::morphMap($valid, merge: true);
    }
}

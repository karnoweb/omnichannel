<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Support;

final class OmnichannelTables
{
    public static function prefix(): string
    {
        return (string) config('omnichannel.general.prefix', 'omc_');
    }

    public static function name(string $key): string
    {
        $prefix = self::prefix();
        $configured = config("omnichannel.tables.{$key}");
        $table = is_string($configured) && $configured !== '' ? $configured : $key;

        if ($prefix === '' || str_starts_with($table, $prefix)) {
            return $table;
        }

        return $prefix . $table;
    }
}

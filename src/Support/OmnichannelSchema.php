<?php

declare(strict_types=1);

namespace Karnoweb\Omnichannel\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\ColumnDefinition;

/**
 * Migration helpers for configurable host-key columns (soft keys only — never hard FKs).
 */
final class OmnichannelSchema
{
    public static function userKey(Blueprint $table, string $column = 'user_id', bool $nullable = true): ColumnDefinition
    {
        return KeyType::apply($table, $column, KeyType::user(), $nullable);
    }

    public static function branchKey(Blueprint $table, string $column = 'branch_id', bool $nullable = true): ColumnDefinition
    {
        return KeyType::apply($table, $column, KeyType::branch(), $nullable);
    }
}

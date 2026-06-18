<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * PostgreSQL-safe JSON column filters.
 * JSON text extraction returns text; compare with explicit casts on pgsql.
 */
final class JsonColumn
{
    public static function whereInt(Builder $query, string $column, string $key, int $value): Builder
    {
        if (self::isPgsql($query)) {
            return $query->whereRaw("({$column}->>'{$key}')::bigint = ?", [$value]);
        }

        return $query->where("{$column}->{$key}", $value);
    }

    public static function whereText(Builder $query, string $column, string $key, string $value): Builder
    {
        if (self::isPgsql($query)) {
            return $query->whereRaw("{$column}->>'{$key}' = ?", [$value]);
        }

        return $query->where("{$column}->{$key}", $value);
    }

    private static function isPgsql(Builder $query): bool
    {
        return DB::connection($query->getConnection()->getName())->getDriverName() === 'pgsql';
    }
}

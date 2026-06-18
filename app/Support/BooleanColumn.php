<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * PostgreSQL-safe boolean bindings for queries and mass updates.
 * (Bind boolean an toàn cho PostgreSQL trong query và update hàng loạt)
 */
final class BooleanColumn
{
    /** @var list<string> */
    public const COLUMNS = [
        'is_read',
        'is_public',
        'is_featured',
        'is_hot',
        'is_new',
        'is_active',
        'is_discrepancy',
        'requires_approval',
        'is_in_scope',
        'cache_hit',
    ];

    public static function isBooleanColumn(string $column): bool
    {
        return in_array($column, self::COLUMNS, true);
    }

    public static function driverIsPgsql(?string $connection = null): bool
    {
        return DB::connection($connection)->getDriverName() === 'pgsql';
    }

    public static function where(Builder $query, string $column, bool $value): Builder
    {
        if (self::driverIsPgsql($query->getConnection()->getName())) {
            return $query->whereRaw($value ? "{$column} IS TRUE" : "{$column} IS FALSE");
        }

        return $query->where($column, $value);
    }

    public static function value(bool $value, ?string $connection = null): mixed
    {
        if (self::driverIsPgsql($connection)) {
            return DB::connection($connection)->raw($value ? 'true' : 'false');
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public static function prepareAttributes(array $attributes, ?string $connection = null): array
    {
        if (! self::driverIsPgsql($connection)) {
            return $attributes;
        }

        foreach ($attributes as $key => $value) {
            if (self::isBooleanColumn((string) $key) && is_bool($value)) {
                $attributes[$key] = self::value($value, $connection);
            }
        }

        return $attributes;
    }

    /**
     * @param  array<int|string, mixed>  $where
     */
    public static function applyWheres(Builder $query, array $where): Builder
    {
        foreach ($where as $key => $value) {
            if (is_int($key) && is_array($value)) {
                if (count($value) === 3) {
                    [$column, $operator, $operand] = $value;
                } elseif (count($value) === 2) {
                    [$column, $operand] = $value;
                    $operator = '=';
                } else {
                    $query->where(...$value);

                    continue;
                }

                if (self::isBooleanColumn((string) $column) && is_bool($operand) && $operator === '=') {
                    self::where($query, (string) $column, $operand);
                } else {
                    $query->where($column, $operator, $operand);
                }

                continue;
            }

            if (self::isBooleanColumn((string) $key) && is_bool($value)) {
                self::where($query, (string) $key, $value);

                continue;
            }

            $query->where($key, $value);
        }

        return $query;
    }
}

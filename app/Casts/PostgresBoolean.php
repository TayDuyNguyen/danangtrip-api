<?php

namespace App\Casts;

use App\Support\BooleanColumn;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * PostgreSQL rejects integer bindings (0/1) for boolean columns.
 * (PostgreSQL không chấp nhận bind integer cho cột boolean)
 */
final class PostgresBoolean implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        $bool = filter_var($value, FILTER_VALIDATE_BOOLEAN);

        return [$key => BooleanColumn::value($bool, $model->getConnectionName())];
    }
}

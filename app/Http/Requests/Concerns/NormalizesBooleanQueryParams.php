<?php

namespace App\Http\Requests\Concerns;

/**
 * Normalizes query-string booleans ("true"/"false"/"0"/"1") before Laravel boolean validation.
 * Axios and URLSearchParams often send "true"/"false" which fail the default boolean rule.
 */
trait NormalizesBooleanQueryParams
{
    /**
     * @param  list<string>  $keys
     */
    protected function mergeBooleanQueryParams(array $keys): void
    {
        $payload = [];

        foreach ($keys as $key) {
            if (! $this->has($key)) {
                continue;
            }

            $parsed = filter_var($this->input($key), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

            if ($parsed !== null) {
                $payload[$key] = $parsed;
            }
        }

        if ($payload !== []) {
            $this->merge($payload);
        }
    }
}

<?php

namespace App\Services\Chat;

final class IntentConsistencyService
{
    public function shouldForceAi(string $intent, array $entities): bool
    {
        $rules = config('chat_consistency', []);

        if (! isset($rules[$intent])) {
            return false;
        }

        $rule = $rules[$intent];
        $abnormalCount = 0;

        foreach (($rule['abnormal'] ?? []) as $field) {
            if (isset($entities[$field]) && $entities[$field] !== null && $entities[$field] !== '') {
                $abnormalCount++;
            }
        }

        return $abnormalCount >= ($rule['threshold'] ?? 1);
    }
}

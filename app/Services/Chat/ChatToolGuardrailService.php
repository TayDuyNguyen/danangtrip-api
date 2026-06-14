<?php

namespace App\Services\Chat;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

final class ChatToolGuardrailService
{
    /**
     * Validate and clean up understanding parameters before search engine/DB queries.
     * Returns an array with 'understanding' (cleaned) and 'warnings' (list of friendly issues found).
     *
     * @param array<string,mixed> $understanding
     * @return array{understanding: array<string,mixed>, warnings: array<int,string>}
     */
    public function validate(array $understanding): array
    {
        $warnings = [];

        // 1. Validate 'people'
        if (isset($understanding['people']) && $understanding['people'] !== null) {
            $people = (int) $understanding['people'];
            if ($people <= 0) {
                $warnings[] = 'invalid_people_count';
                $understanding['people'] = null;
            } elseif ($people > 100) {
                $warnings[] = 'excessive_people_count';
                // Keep it, but log a warning or cap it
            }
        }

        // 2. Validate 'date'
        if (isset($understanding['date']) && $understanding['date'] !== null) {
            try {
                $dateStr = (string) $understanding['date'];
                $carbonDate = Carbon::parse($dateStr);
                $today = Carbon::today();

                if ($carbonDate->isBefore($today)) {
                    $warnings[] = 'past_date';
                    $understanding['date'] = null; // Reset to avoid querying past tours
                }
            } catch (\Throwable $e) {
                Log::warning('CHATBOT_GUARDRAIL_DATE_PARSE_FAILED', ['date' => $understanding['date'], 'error' => $e->getMessage()]);
                $understanding['date'] = null;
            }
        }

        // 3. Validate price bounds
        if (isset($understanding['max_price']) && $understanding['max_price'] !== null) {
            $maxPrice = (float) $understanding['max_price'];
            if ($maxPrice <= 0) {
                $warnings[] = 'invalid_max_price';
                $understanding['max_price'] = null;
            }
        }

        if (isset($understanding['min_price']) && $understanding['min_price'] !== null) {
            $minPrice = (float) $understanding['min_price'];
            if ($minPrice <= 0) {
                $warnings[] = 'invalid_min_price';
                $understanding['min_price'] = null;
            }
        }

        // Check cross-price consistency
        if (
            isset($understanding['min_price'], $understanding['max_price']) &&
            $understanding['min_price'] !== null &&
            $understanding['max_price'] !== null
        ) {
            if ($understanding['min_price'] > $understanding['max_price']) {
                $warnings[] = 'price_min_greater_than_max';
                // Swap them or reset min_price
                $temp = $understanding['max_price'];
                $understanding['max_price'] = $understanding['min_price'];
                $understanding['min_price'] = $temp;
            }
        }

        return [
            'understanding' => $understanding,
            'warnings' => $warnings,
        ];
    }
}

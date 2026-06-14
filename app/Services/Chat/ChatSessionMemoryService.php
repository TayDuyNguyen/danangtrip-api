<?php

namespace App\Services\Chat;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

final class ChatSessionMemoryService
{
    private const CACHE_PREFIX = 'chatbot_session_';
    private const TTL = 1800; // 30 minutes

    /**
     * Load session memory.
     *
     * @return array<string,mixed>
     */
    public function loadSession(string $sessionId): array
    {
        try {
            $session = Cache::get(self::CACHE_PREFIX . $sessionId);
            if (!$session) {
                return [
                    'intent' => null,
                    'slots' => [
                        'destination' => null,
                        'people' => null,
                        'max_price' => null,
                        'date' => null,
                    ],
                    'clarification_step' => null,
                    'clarification_attempts' => 0,
                    'updated_at' => null,
                ];
            }
            if (!isset($session['clarification_attempts'])) {
                $session['clarification_attempts'] = 0;
            }
            return $session;
        } catch (\Throwable $e) {
            Log::warning('CHATBOT_SESSION_LOAD_FAILED', ['message' => $e->getMessage()]);
            return [
                'intent' => null,
                'slots' => [
                    'destination' => null,
                    'people' => null,
                    'max_price' => null,
                    'date' => null,
                ],
                'clarification_step' => null,
                'clarification_attempts' => 0,
                'updated_at' => null,
            ];
        }
    }

    /**
     * Save session memory.
     */
    public function saveSession(string $sessionId, array $sessionData): void
    {
        try {
            $sessionData['updated_at'] = now()->toDateTimeString();
            Cache::put(self::CACHE_PREFIX . $sessionId, $sessionData, self::TTL);
        } catch (\Throwable $e) {
            Log::warning('CHATBOT_SESSION_SAVE_FAILED', ['message' => $e->getMessage()]);
        }
    }

    /**
     * Clear session memory.
     */
    public function clearSession(string $sessionId): void
    {
        try {
            Cache::forget(self::CACHE_PREFIX . $sessionId);
        } catch (\Throwable $e) {
            Log::warning('CHATBOT_SESSION_CLEAR_FAILED', ['message' => $e->getMessage()]);
        }
    }

    /**
     * Merge new understanding into the session slots and determine clarification steps.
     */
    public function updateSession(string $sessionId, array $understanding, string $intent): array
    {
        $session = $this->loadSession($sessionId);

        // Reset slots if changing intent topic completely (exclude unknown/greeting/general)
        if ($session['intent'] !== null && $session['intent'] !== $intent && !in_array($intent, ['unknown', 'greeting'], true)) {
            if (in_array($intent, ['tour', 'booking', 'location', 'food', 'hotel'], true)) {
                $session['slots'] = [
                    'destination' => null,
                    'people' => null,
                    'max_price' => null,
                    'date' => null,
                ];
            }
        }

        if (!in_array($intent, ['unknown'], true)) {
            $session['intent'] = $intent;
        } elseif ($session['intent'] !== null) {
            // Retain previous intent if current is unknown (replying to clarification)
            $intent = $session['intent'];
        }

        $oldClarificationStep = $session['clarification_step'];

        // Merge standard NLU entities
        foreach (['destination', 'people', 'max_price', 'date'] as $slot) {
            if (isset($understanding[$slot]) && $understanding[$slot] !== null && $understanding[$slot] !== '') {
                $session['slots'][$slot] = $understanding[$slot];
            }
        }

        // Special check: if we were waiting for 'people', and the user didn't get parsed
        // with the 'people' entity but typed a number, extract it.
        if ($oldClarificationStep === 'people' && empty($session['slots']['people'])) {
            $num = $this->extractNumber($understanding['original_question'] ?? '');
            if ($num > 0) {
                $session['slots']['people'] = $num;
            }
        }

        // Special check: if we were waiting for 'destination', and the user replied with a short text
        if ($oldClarificationStep === 'destination' && empty($session['slots']['destination'])) {
            $text = trim($understanding['original_question'] ?? '');
            if ($text !== '' && mb_strlen($text) < 40) {
                $session['slots']['destination'] = $text;
            }
        }

        // Determine next clarification step
        $session['clarification_step'] = null;
        if (in_array($intent, ['tour', 'booking'], true)) {
            if (empty($session['slots']['destination'])) {
                $session['clarification_step'] = 'destination';
            } elseif (empty($session['slots']['people'])) {
                $session['clarification_step'] = 'people';
            }
        }

        // Increment or reset clarification attempts
        if ($session['clarification_step'] !== null && $session['clarification_step'] === $oldClarificationStep) {
            $session['clarification_attempts'] = ($session['clarification_attempts'] ?? 0) + 1;
        } else {
            $session['clarification_attempts'] = 0;
        }

        // Apply attempt limit
        $limit = (int) config('chatbot.clarification_attempt_limit', 2);
        if ($session['clarification_step'] !== null && $session['clarification_attempts'] >= $limit) {
            $session['clarification_step'] = null;
            $session['clarification_attempts'] = 0;
        }

        $this->saveSession($sessionId, $session);

        return $session;
    }

    private function extractNumber(string $text): int
    {
        if (preg_match('/(\d+)\s*(người|nguoi|pax|khách|khach|vé|ve)/i', $text, $matches)) {
            return (int) $matches[1];
        }
        if (preg_match('/^\s*(\d+)\s*$/', $text, $matches)) {
            return (int) $matches[1];
        }
        $words = [
            'một' => 1, 'mot' => 1, 'hai' => 2, 'ba' => 3, 'bốn' => 4, 'bon' => 4,
            'năm' => 5, 'nam' => 5, 'sáu' => 6, 'sau' => 6, 'bảy' => 7, 'bay' => 7,
            'tám' => 8, 'tam' => 8, 'chín' => 9, 'chin' => 9, 'mười' => 10, 'muoi' => 10
        ];
        foreach ($words as $word => $val) {
            if (mb_stripos($text, $word) !== false) {
                return $val;
            }
        }
        return 0;
    }
}

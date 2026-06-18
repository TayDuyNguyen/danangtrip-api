<?php

namespace App\Services\Chat;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

final class ChatSessionMemoryService
{
    private const CACHE_PREFIX = 'chatbot_session_';

    private const TTL = 3600; // 1 hour

    /**
     * Tải thông tin phiên làm việc của chatbot từ bộ nhớ cache.
     * Nếu không có phiên làm việc tồn tại, trả về cấu trúc mặc định.
     *
     * @param  string  $sessionId  ID của phiên làm việc
     * @return array<string,mixed> Dữ liệu phiên làm việc
     */
    public function loadSession(string $sessionId): array
    {
        try {
            $session = Cache::get(self::CACHE_PREFIX.$sessionId);
            if (! $session) {
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
                    'context_signature' => '',
                    'updated_at' => null,
                ];
            }
            if (! isset($session['clarification_attempts'])) {
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
                'context_signature' => '',
                'updated_at' => null,
            ];
        }
    }

    /**
     * Lưu thông tin phiên làm việc của chatbot vào bộ nhớ cache.
     *
     * @param  string  $sessionId  ID của phiên làm việc
     * @param  array<string,mixed>  $sessionData  Dữ liệu phiên làm việc cần lưu
     */
    public function saveSession(string $sessionId, array $sessionData): void
    {
        try {
            $sessionData['updated_at'] = now()->toDateTimeString();
            $ttl = (int) config('chatbot.session_ttl_seconds', self::TTL);
            Cache::put(self::CACHE_PREFIX.$sessionId, $sessionData, $ttl);
        } catch (\Throwable $e) {
            Log::warning('CHATBOT_SESSION_SAVE_FAILED', ['message' => $e->getMessage()]);
        }
    }

    /**
     * Xóa thông tin phiên làm việc của chatbot khỏi bộ nhớ cache.
     *
     * @param  string  $sessionId  ID của phiên làm việc
     */
    public function clearSession(string $sessionId): void
    {
        try {
            Cache::forget(self::CACHE_PREFIX.$sessionId);
        } catch (\Throwable $e) {
            Log::warning('CHATBOT_SESSION_CLEAR_FAILED', ['message' => $e->getMessage()]);
        }
    }

    /**
     * Cập nhật thông tin phiên làm việc dựa trên kết quả phân tích câu hỏi mới.
     * Đồng thời tự động cập nhật các slots và xác định bước làm rõ (clarification) tiếp theo.
     *
     * @param  string  $sessionId  ID của phiên làm việc
     * @param  array<string,mixed>  $understanding  Kết quả phân tích câu hỏi từ NLU
     * @param  string  $intent  Ý định hiện tại của người dùng
     * @param  array<string,mixed>  $context  Ngữ cảnh trang hiện tại
     * @return array<string,mixed> Dữ liệu phiên làm việc sau khi cập nhật
     */
    public function updateSession(
        string $sessionId,
        array $understanding,
        string $intent,
        array $context = []
    ): array {
        $session = $this->loadSession($sessionId);
        $contextSignature = $this->contextSignature($context);
        $previousContextSignature = (string) ($session['context_signature'] ?? '');
        $contextChanged = $contextSignature !== ''
            && $previousContextSignature !== ''
            && $contextSignature !== $previousContextSignature;

        if ($contextChanged) {
            $session['slots'] = [
                'destination' => null,
                'people' => null,
                'max_price' => null,
                'date' => null,
            ];
            $session['clarification_step'] = null;
            $session['clarification_attempts'] = 0;
        }

        if ($contextSignature !== '') {
            $session['context_signature'] = $contextSignature;
        }

        // Chỉ giữ intent cũ khi câu trả lời thực sự không xác định. Location là một
        // intent hợp lệ và phải được phép thoát khỏi clarification của tour.
        if ($session['clarification_step'] !== null && $intent === 'unknown' && $session['intent'] !== null) {
            $intent = $session['intent'];
        }

        // Reset slots if changing intent topic completely (exclude unknown/greeting/general)
        if ($session['intent'] !== null && $session['intent'] !== $intent && ! in_array($intent, ['unknown', 'greeting'], true)) {
            if (in_array($intent, ['tour', 'booking', 'location', 'food', 'hotel'], true)) {
                $session['slots'] = [
                    'destination' => null,
                    'people' => null,
                    'max_price' => null,
                    'date' => null,
                ];
            }
        }

        if (! in_array($intent, ['unknown'], true)) {
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

    /**
     * @param  array<string,mixed>  $context
     */
    private function contextSignature(array $context): string
    {
        $parts = [
            (string) ($context['page_type'] ?? ''),
            (string) ($context['entity_type'] ?? ''),
            (string) ($context['entity_id'] ?? ''),
            (string) ($context['entity_slug'] ?? ''),
        ];

        return trim(implode(':', $parts), ':');
    }

    /**
     * Phân tích và trích xuất số lượng từ văn bản đầu vào.
     * Hỗ trợ phát hiện khoảng số, số đứng một mình, hoặc số bằng chữ tiếng Việt.
     *
     * @param  string  $text  Văn bản đầu vào cần phân tích
     * @return int Số được trích xuất (mặc định trả về 0 nếu không tìm thấy)
     */
    private function extractNumber(string $text): int
    {
        // Pattern: dạng khoảng "3 - 5 người", "3-5 pax" → lấy số cuối (max)
        if (preg_match('/(\d+)\s*[-–]\s*(\d+)\s*(người|nguoi|pax|khách|khach|vé|ve|ng|ngs)?/iu', $text, $matches)) {
            return (int) $matches[2]; // lấy số lớn hơn trong khoảng
        }

        // Pattern: "3 người", "5 pax", "2 khách"
        if (preg_match('/(\d+)\s*(người|nguoi|pax|khách|khach|vé|ve|ng|ngs)/iu', $text, $matches)) {
            return (int) $matches[1];
        }

        // Pattern: "đoàn 3", "đoàn 4", "nhóm 5" (đứng trước số)
        if (preg_match('/(?:đoàn|doan|nhóm|nhom|group)\s+(\d+)/iu', $text, $matches)) {
            return (int) $matches[1];
        }

        // Pattern: số đứng một mình "5", "3"
        if (preg_match('/^\s*(\d+)\s*$/', $text, $matches)) {
            return (int) $matches[1];
        }

        // Pattern: số bất kỳ trong câu (fallback)
        if (preg_match('/\b(\d{1,2})\b/', $text, $matches)) {
            $n = (int) $matches[1];
            if ($n >= 1 && $n <= 50) {
                return $n;
            }
        }

        $words = [
            'một' => 1, 'mot' => 1, 'hai' => 2, 'ba' => 3, 'bốn' => 4, 'bon' => 4,
            'năm' => 5, 'nam' => 5, 'sáu' => 6, 'sau' => 6, 'bảy' => 7, 'bay' => 7,
            'tám' => 8, 'tam' => 8, 'chín' => 9, 'chin' => 9, 'mười' => 10, 'muoi' => 10,
        ];
        foreach ($words as $word => $val) {
            if (mb_stripos($text, $word) !== false) {
                return $val;
            }
        }

        return 0;
    }
}

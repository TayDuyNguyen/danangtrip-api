<?php

namespace App\Services\Chat;

use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

final class ChatAiProviderService
{
    private array $logs = [];

    public function clearLogs(): void
    {
        $this->logs = [];
    }

    public function getLogs(): array
    {
        return $this->logs;
    }

    private function addLog(string $message): void
    {
        $this->logs[] = $message;
    }

    /** @param array<int,array{role:string,content:string}> $messages */
    public function complete(array $messages, array $options = []): array
    {
        $attempts = 0;
        $maxAttempts = max(1, (int) config('chatbot.max_retries', 3));

        foreach ((array) config('chatbot.provider_order', []) as $provider) {
            $providerConfig = (array) config("chatbot.providers.{$provider}", []);
            $keys = (array) ($providerConfig['keys'] ?? []);

            foreach ($keys as $index => $key) {
                if ($attempts >= $maxAttempts) {
                    break 2;
                }

                if ($this->isCoolingDown($provider, $index)) {
                    $this->addLog("Skipped key {$index} for provider '{$provider}' (cooling down)");

                    continue;
                }

                $attempts++;

                try {
                    $result = match ($provider) {
                        'gemini' => $this->completeGemini($messages, $providerConfig, (string) $key, $index, $options),
                        'groq', 'openrouter', 'openai' => $this->completeOpenAiCompatible($messages, $provider, $providerConfig, (string) $key, $index, $options),
                        default => throw new RuntimeException("Unsupported chatbot provider: {$provider}"),
                    };

                    $this->addLog("AI Completion SUCCESS with provider '{$provider}', model '".($providerConfig['model'] ?? 'N/A')."', key_index={$index}");

                    return [
                        'ok' => true,
                        'text' => $result['text'],
                        'provider' => $provider,
                        'model' => $providerConfig['model'] ?? null,
                        'tokens_used' => $result['tokens_used'] ?? 0,
                        'attempts' => $attempts,
                    ];
                } catch (\Throwable $e) {
                    $this->addLog("AI Completion FAILED for provider '{$provider}', key_index={$index}, error: ".$e->getMessage());
                    Log::warning('CHATBOT_PROVIDER_FAILED', [
                        'provider' => $provider,
                        'model' => $providerConfig['model'] ?? null,
                        'key_index' => $index,
                        'key_hash' => substr(hash('sha256', (string) $key), 0, 10),
                        'message' => $e->getMessage(),
                    ]);
                }
            }
        }

        return [
            'ok' => false,
            'text' => null,
            'provider' => null,
            'model' => null,
            'tokens_used' => 0,
            'attempts' => $attempts,
        ];
    }

    /**
     * Trích xuất entities từ câu hỏi bằng AI NLU — Schema mở rộng.
     *
     * Schema trả về:
     * {
     *   "intent": string,
     *   "topics": string[],
     *   "content_types": string[],
     *   "keywords": string[],
     *   "destination": string|null,
     *   "region": string|null,
     *   "max_price": int|null,
     *   "min_price": int|null,
     *   "people": int|null,
     *   "date": string|null,
     *   "duration_days": int|null,
     *   "cheapest_first": boolean,
     *   "best_first": boolean
     * }
     *
     * @return array<string,mixed>
     */
    public function extractEntitiesWithAi(string $question, string $locale, array $currentEntities, string $detectedIntent = '', string $reason = ''): ?array
    {
        $provider = 'gemini';
        $providerConfig = (array) config("chatbot.providers.{$provider}", []);
        $keys = (array) ($providerConfig['keys'] ?? []);

        $currentDate = CarbonImmutable::now('Asia/Ho_Chi_Minh')->locale($locale);
        $dateInfo = 'Hôm nay là: '.$currentDate->isoFormat('dddd, DD/MM/YYYY').' (múi giờ Asia/Ho_Chi_Minh).';
        if ($locale === 'en') {
            $dateInfo = 'Today is: '.$currentDate->isoFormat('dddd, MMMM DD, YYYY').' (timezone Asia/Ho_Chi_Minh).';
        }

        // Danh sách topics hợp lệ
        $validTopics = implode(', ', [
            'local_food', 'restaurant', 'cafe', 'seafood', 'street_food',
            'hotel', 'resort', 'homestay',
            'beach', 'mountain', 'temple', 'museum', 'market', 'park', 'island',
            'family_friendly', 'romantic', 'budget', 'luxury',
            'adventure', 'cultural', 'nature',
        ]);

        $systemPrompt = implode("\n", [
            'Bạn là hệ thống NLU (Natural Language Understanding) chuyên phân tích câu hỏi du lịch Đà Nẵng và miền Trung Việt Nam.',
            $dateInfo,
            '',
            'NHIỆM VỤ: Phân tích câu hỏi du lịch, xác thực hoặc điều chỉnh thực thể và intent và trả về JSON theo đúng schema sau (KHÔNG giải thích, CHỈ trả JSON):',
            '{',
            '  "intent": "<string>",',
            '  // intent phải là một trong: tour | location | food | hotel | blog | schedule | booking | payment | refund | loyalty | account | contact | greeting | unknown',
            '',
            '  "confidence": <float>,',
            '  // Độ tin cậy của phân loại intent (giá trị float từ 0.0 đến 1.0)',
            '',
            '  "topics": ["<string>", ...],',
            '  // Chủ đề cụ thể (chọn từ danh sách): '.$validTopics,
            '',
            '  "content_types": ["<string>", ...],',
            '  // Loại nội dung cần tìm (chọn từ): tour | location | blog | policy',
            '  // Ví dụ: "Ăn gì?" → ["location", "blog"], "Tour Bà Nà" → ["tour"], "Chính sách hoàn tiền" → ["policy"]',
            '',
            '  "keywords": ["<string>", ...],',
            '  // 2–5 từ khóa tìm kiếm tiếng Việt ngắn gọn (không dấu OK, có dấu tốt hơn)',
            '  // Ví dụ: ["đặc sản Đà Nẵng", "quán ăn", "gia đình"]',
            '',
            '  "destination": "<string>" hoặc null,',
            '  // Địa điểm/thắng cảnh cụ thể. Ví dụ: "Bà Nà Hills", "Hội An", "Huế", "Cù Lao Chàm", "Ngũ Hành Sơn"',
            '',
            '  "region": "<string>" hoặc null,',
            '  // Vùng/Thành phố. Ví dụ: "Đà Nẵng", "Hội An", "Huế", "Quảng Nam"',
            '',
            '  "max_price": <int> hoặc null,',
            '  // Giá tối đa VNĐ. "2 triệu" → 2000000, "500k" → 500000',
            '',
            '  "min_price": <int> hoặc null,',
            '  // Giá tối thiểu VNĐ',
            '',
            '  "people": <int> hoặc null,',
            '  // Số người/khách. "4 người" → 4',
            '',
            '  "date": "<YYYY-MM-DD>" hoặc null,',
            '  // Dùng ngày hôm nay và thứ để tính đúng: "ngày mai", "cuối tuần này", "cuối tuần sau"',
            '',
            '  "duration_days": <int> hoặc null,',
            '  // Số ngày. "3 ngày" → 3',
            '',
            '  "cheapest_first": <boolean>,',
            '  // true nếu muốn rẻ nhất/giá thấp nhất',
            '',
            '  "best_first": <boolean>',
            '  // true nếu muốn tốt nhất/nổi bật/đánh giá cao nhất',
            '}',
            '',
            'LƯU Ý QUAN TRỌNG VỀ Ý ĐỊNH (INTENT) VÀ LOẠI NỘI DUNG (CONTENT TYPES):',
            '- "tour": Người dùng muốn đặt tour, đi tour ghép/riêng, tìm lịch trình trọn gói. ĐẶC BIỆT: Khi câu hỏi hỏi về địa điểm tham quan/du lịch (ví dụ: "Cầu Rồng", "Bà Nà Hills") nhưng lại đi kèm thông tin về số lượng người ("4 người"), ngân sách ("1.5 triệu"), hoặc thời lượng ("3 ngày"), thì ý định thực sự là tìm tour du lịch ("tour"), không phải là tự đi tự túc ("location"). Khi đó, "intent" phải là "tour" và "content_types" phải là ["tour"].',
            '- "location": Người dùng chỉ muốn tìm thông tin giới thiệu, địa chỉ, giờ mở cửa của các địa điểm vui chơi, thắng cảnh tự túc, nhà hàng, khách sạn mà KHÔNG có các thông tin ràng buộc về số lượng người hay ngân sách đặt tour đi kèm.',
            '- Đầu vào có "reason": "consistency_failed" báo hiệu hệ thống rule-based phân loại nhầm thành "location" hoặc "blog" nhưng thực tế có thông tin ràng buộc (ngân sách, số người, thời gian đi). Khi đó hãy chuyển đổi "intent" sang "tour" và "content_types" sang ["tour"].',
            '',
            'Quy tắc trả về:',
            '1. CHỈ trả về JSON object duy nhất, KHÔNG thêm bất kỳ giải thích hay text nào bên ngoài JSON.',
            '2. Nhận đầu vào dưới dạng cấu trúc JSON gồm thông tin phân tích rule-based ban đầu.',
            '3. Hãy kế thừa, xác nhận hoặc sửa lại intent và thực thể cho phù hợp dựa trên ngữ nghĩa của toàn bộ câu hỏi.',
        ]);

        foreach ($keys as $index => $key) {
            if ($this->isCoolingDown($provider, $index)) {
                $this->addLog("Skipped NLU key {$index} for provider '{$provider}' (cooling down)");

                continue;
            }

            try {
                $model = (string) ($providerConfig['model'] ?? 'gemini-2.5-flash');
                $baseUrl = rtrim((string) ($providerConfig['base_url'] ?? 'https://generativelanguage.googleapis.com/v1beta'), '/');

                $response = Http::timeout((int) config('chatbot.timeout_seconds', 25))
                    ->post("{$baseUrl}/models/{$model}:generateContent?key={$key}", [
                        'contents' => [
                            [
                                'role' => 'user',
                                'parts' => [
                                    ['text' => implode("\n\n", [
                                        'SYSTEM: '.$systemPrompt,
                                        'STRUCTURED CONTEXT (INPUT): '.json_encode([
                                            'question' => $question,
                                            'rule_intent' => $detectedIntent,
                                            'entities' => $currentEntities,
                                            'reason' => $reason,
                                        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                                    ])],
                                ],
                            ],
                        ],
                        'generationConfig' => [
                            'temperature' => 0.1,
                            'responseMimeType' => 'application/json',
                            'thinkingConfig' => [
                                'thinkingBudget' => 0,
                            ],
                        ],
                    ]);

                $this->ensureSuccessfulResponse($response, $provider, $index);

                $text = (string) data_get($response->json(), 'candidates.0.content.parts.0.text', '');
                $extracted = json_decode(trim($text), true);

                if (is_array($extracted)) {
                    $this->addLog("AI NLU SUCCESS with provider '{$provider}', model '{$model}', key_index={$index}");

                    return $this->mergeEntities($currentEntities, $extracted);
                }
            } catch (\Throwable $e) {
                $this->addLog("AI NLU FAILED for provider '{$provider}', key_index={$index}, error: ".$e->getMessage());
                Log::warning('CHATBOT_AI_NLU_FAILED', [
                    'provider' => $provider,
                    'key_index' => $index,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return null;
    }

    /**
     * Merge rule-based entities với AI NLU entities.
     * AI NLU override rule-based nếu có giá trị mới.
     *
     * @return array<string,mixed>
     */
    private function mergeEntities(array $current, array $aiExtracted): array
    {
        $merged = $current;

        // Override với AI values nếu không null/empty
        $scalarFields = ['intent', 'confidence', 'destination', 'region', 'max_price', 'min_price', 'people', 'date', 'duration_days'];
        foreach ($scalarFields as $field) {
            if (isset($aiExtracted[$field]) && $aiExtracted[$field] !== null && $aiExtracted[$field] !== '') {
                $merged[$field] = $aiExtracted[$field];
            }
        }

        // Array fields: override if present in AI response, otherwise keep rule-based
        $arrayFields = ['topics', 'content_types', 'keywords'];
        foreach ($arrayFields as $field) {
            if (isset($aiExtracted[$field]) && is_array($aiExtracted[$field])) {
                $merged[$field] = $aiExtracted[$field];
            }
        }

        // Boolean fields
        $merged['cheapest_first'] = filter_var($aiExtracted['cheapest_first'] ?? $current['cheapest_first'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $merged['best_first'] = filter_var($aiExtracted['best_first'] ?? $current['best_first'] ?? false, FILTER_VALIDATE_BOOLEAN);

        // Migrate content_type_hints → content_types
        if (! empty($current['content_type_hints']) && ! isset($merged['content_types'])) {
            $merged['content_types'] = $current['content_type_hints'];
        }

        // Migrate topic_hints → topics
        if (! empty($current['topic_hints']) && ! isset($merged['topics'])) {
            $merged['topics'] = $current['topic_hints'];
        }

        return $merged;
    }

    /** @param array<int,array{role:string,content:string}> $messages */
    private function completeGemini(array $messages, array $providerConfig, string $key, int $keyIndex, array $options = []): array
    {
        $model = (string) ($providerConfig['model'] ?? 'gemini-2.5-flash');
        $baseUrl = rtrim((string) ($providerConfig['base_url'] ?? 'https://generativelanguage.googleapis.com/v1beta'), '/');
        $system = $this->extractSystemMessage($messages);
        $prompt = trim($system."\n\n".$this->messagesToPrompt($messages));

        $response = Http::timeout((int) config('chatbot.timeout_seconds', 25))
            ->post("{$baseUrl}/models/{$model}:generateContent?key={$key}", [
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [
                            ['text' => $prompt],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'temperature' => (float) ($options['temperature'] ?? config('chatbot.temperature', 0.3)),
                    'maxOutputTokens' => (int) ($options['max_tokens'] ?? config('chatbot.max_tokens', 700)),
                    'thinkingConfig' => [
                        'thinkingBudget' => 0,
                    ],
                ],
            ]);

        $this->ensureSuccessfulResponse($response, 'gemini', $keyIndex);

        $text = (string) data_get($response->json(), 'candidates.0.content.parts.0.text', '');
        if (trim($text) === '') {
            throw new RuntimeException('Gemini returned an empty response.');
        }

        return [
            'text' => trim($text),
            'tokens_used' => (int) data_get($response->json(), 'usageMetadata.totalTokenCount', 0),
        ];
    }

    /** @param array<int,array{role:string,content:string}> $messages */
    private function completeOpenAiCompatible(array $messages, string $provider, array $providerConfig, string $key, int $keyIndex, array $options = []): array
    {
        $baseUrl = rtrim((string) ($providerConfig['base_url'] ?? ''), '/');
        $headers = [
            'Authorization' => "Bearer {$key}",
            'Content-Type' => 'application/json',
        ];

        if ($provider === 'openrouter') {
            if (! empty($providerConfig['site_url'])) {
                $headers['HTTP-Referer'] = (string) $providerConfig['site_url'];
            }
            if (! empty($providerConfig['app_name'])) {
                $headers['X-Title'] = (string) $providerConfig['app_name'];
            }
        }

        $response = Http::withHeaders($headers)
            ->timeout((int) config('chatbot.timeout_seconds', 25))
            ->post("{$baseUrl}/chat/completions", [
                'model' => (string) ($providerConfig['model'] ?? ''),
                'messages' => $messages,
                'temperature' => (float) ($options['temperature'] ?? config('chatbot.temperature', 0.3)),
                'max_tokens' => (int) ($options['max_tokens'] ?? config('chatbot.max_tokens', 700)),
            ]);

        $this->ensureSuccessfulResponse($response, $provider, $keyIndex);

        $text = (string) data_get($response->json(), 'choices.0.message.content', '');
        if (trim($text) === '') {
            throw new RuntimeException("{$provider} returned an empty response.");
        }

        return [
            'text' => trim($text),
            'tokens_used' => (int) data_get($response->json(), 'usage.total_tokens', 0),
        ];
    }

    private function ensureSuccessfulResponse(Response $response, string $provider, int $keyIndex): void
    {
        if ($response->successful()) {
            return;
        }

        $status = $response->status();
        $message = (string) (
            data_get($response->json(), 'error.message')
            ?: data_get($response->json(), 'message')
            ?: $response->body()
        );

        if (in_array($status, (array) config('chatbot.failover_status_codes', []), true) || in_array($status, [401, 403], true)) {
            $cooldownTime = 3600; // default 1 hour
            if (in_array($status, [401, 403], true)) {
                $cooldownTime = 86400; // 1 day for invalid keys
            } elseif (in_array($status, [429, 503], true)) {
                $cooldownTime = 60; // 60 seconds for rate limit or temp overload
            } else {
                $cooldownTime = (int) config('chatbot.key_cooldown_seconds', 3600);
            }
            $this->coolDown($provider, $keyIndex, $cooldownTime);
        }

        throw new RuntimeException("{$provider} HTTP {$status}: ".mb_substr($message, 0, 300));
    }

    /** @param array<int,array{role:string,content:string}> $messages */
    private function extractSystemMessage(array $messages): string
    {
        foreach ($messages as $message) {
            if (($message['role'] ?? '') === 'system') {
                return (string) $message['content'];
            }
        }

        return '';
    }

    /** @param array<int,array{role:string,content:string}> $messages */
    private function messagesToPrompt(array $messages): string
    {
        return collect($messages)
            ->reject(fn (array $message) => ($message['role'] ?? '') === 'system')
            ->map(fn (array $message) => strtoupper((string) $message['role']).":\n".(string) $message['content'])
            ->implode("\n\n");
    }

    private function isCoolingDown(string $provider, int $keyIndex): bool
    {
        return Cache::has($this->cooldownKey($provider, $keyIndex));
    }

    private function coolDown(string $provider, int $keyIndex, ?int $seconds = null): void
    {
        Cache::put(
            $this->cooldownKey($provider, $keyIndex),
            true,
            $seconds ?? (int) config('chatbot.key_cooldown_seconds', 3600)
        );
    }

    private function cooldownKey(string $provider, int $keyIndex): string
    {
        return "chatbot:provider_cooldown:{$provider}:{$keyIndex}";
    }
}

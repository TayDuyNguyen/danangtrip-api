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
    /** @param array<int,array{role:string,content:string}> $messages */
    public function complete(array $messages): array
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
                    continue;
                }

                $attempts++;

                try {
                    $result = match ($provider) {
                        'gemini' => $this->completeGemini($messages, $providerConfig, (string) $key, $index),
                        'groq', 'openrouter', 'openai' => $this->completeOpenAiCompatible($messages, $provider, $providerConfig, (string) $key, $index),
                        default => throw new RuntimeException("Unsupported chatbot provider: {$provider}"),
                    };

                    return [
                        'ok' => true,
                        'text' => $result['text'],
                        'provider' => $provider,
                        'model' => $providerConfig['model'] ?? null,
                        'tokens_used' => $result['tokens_used'] ?? 0,
                        'attempts' => $attempts,
                    ];
                } catch (\Throwable $e) {
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

    /** @return array<string,mixed> */
    public function extractEntitiesWithAi(string $question, string $locale, array $currentEntities): array
    {
        $provider = 'gemini';
        $providerConfig = (array) config("chatbot.providers.{$provider}", []);
        $keys = (array) ($providerConfig['keys'] ?? []);

        $currentDate = CarbonImmutable::now('Asia/Ho_Chi_Minh')->locale($locale);
        $dateInfo = 'Hôm nay là: '.$currentDate->isoFormat('dddd, DD/MM/YYYY').' (múi giờ Asia/Ho_Chi_Minh).';
        if ($locale === 'en') {
            $dateInfo = 'Today is: '.$currentDate->isoFormat('dddd, MMMM DD, YYYY').' (timezone Asia/Ho_Chi_Minh).';
        }

        $systemPrompt = implode("\n", [
            'Bạn là một hệ thống NLU (Natural Language Understanding) chuyên trích xuất thực thể từ câu hỏi du lịch của người dùng.',
            $dateInfo,
            'Hãy phân tích câu hỏi và trích xuất các thông tin dưới dạng JSON theo đúng schema sau:',
            '{',
            "  \"destination\": string|null, // Tên địa điểm du lịch cụ thể (ví dụ: 'Bà Nà Hills', 'Hội An', 'Huế'...)",
            "  \"region\": string|null, // Vùng/Thành phố (ví dụ: 'Đà Nẵng', 'Hội An', 'Huế', 'Quảng Nam')",
            "  \"max_price\": int|null, // Mức giá tối đa mà khách có thể trả (quy đổi hết sang VNĐ, ví dụ: '2 triệu' -> 2000000, '500k' -> 500000)",
            '  "min_price": int|null, // Mức giá tối thiểu',
            "  \"people\": int|null, // Số lượng người/khách đi tour (ví dụ: '2 người' -> 2)",
            "  \"date\": string|null, // Ngày khởi hành mong muốn dạng YYYY-MM-DD (Hãy dùng ngày hôm nay và thứ của tuần để tính toán chính xác các mốc như 'cuối tuần này', 'cuối tuần sau', 'ngày mai')",
            "  \"duration_days\": int|null, // Số ngày của lịch trình (ví dụ: '3 ngày' -> 3)",
            '  "cheapest_first": boolean, // true nếu người dùng muốn tìm giá rẻ nhất',
            '  "best_first": boolean // true nếu người dùng muốn tìm tour tốt nhất/nổi bật nhất',
            '}',
            'LƯU Ý QUAN TRỌNG:',
            '1. Chỉ trả về một đối tượng JSON duy nhất, không thêm bất kỳ văn bản giải thích nào trước hoặc sau JSON.',
            '2. Sử dụng thông tin trích xuất hiện tại (nếu có): '.json_encode($currentEntities, JSON_UNESCAPED_UNICODE),
            '3. Không tự chế thông tin. Nếu không trích xuất được trường nào, hãy để giá trị là null hoặc giữ nguyên giá trị hiện tại.',
        ]);

        foreach ($keys as $index => $key) {
            if ($this->isCoolingDown($provider, $index)) {
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
                                        'USER QUESTION: '.$question,
                                    ])],
                                ],
                            ],
                        ],
                        'generationConfig' => [
                            'temperature' => 0.1,
                            'responseMimeType' => 'application/json',
                        ],
                    ]);

                $this->ensureSuccessfulResponse($response, $provider, $index);

                $text = (string) data_get($response->json(), 'candidates.0.content.parts.0.text', '');
                $extracted = json_decode(trim($text), true);

                if (is_array($extracted)) {
                    $merged = array_merge($currentEntities, array_filter($extracted, fn ($v) => $v !== null));
                    // Ensure boolean values are mapped properly
                    $merged['cheapest_first'] = filter_var($extracted['cheapest_first'] ?? $currentEntities['cheapest_first'], FILTER_VALIDATE_BOOLEAN);
                    $merged['best_first'] = filter_var($extracted['best_first'] ?? $currentEntities['best_first'], FILTER_VALIDATE_BOOLEAN);

                    return $merged;
                }
            } catch (\Throwable $e) {
                Log::warning('CHATBOT_AI_NLU_FAILED', [
                    'provider' => $provider,
                    'key_index' => $index,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return $currentEntities;
    }

    /** @param array<int,array{role:string,content:string}> $messages */
    private function completeGemini(array $messages, array $providerConfig, string $key, int $keyIndex): array
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
                    'temperature' => (float) config('chatbot.temperature', 0.3),
                    'maxOutputTokens' => (int) config('chatbot.max_tokens', 700),
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
    private function completeOpenAiCompatible(array $messages, string $provider, array $providerConfig, string $key, int $keyIndex): array
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
                'temperature' => (float) config('chatbot.temperature', 0.3),
                'max_tokens' => (int) config('chatbot.max_tokens', 700),
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
            $this->coolDown($provider, $keyIndex, in_array($status, [401, 403], true) ? 86400 : null);
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

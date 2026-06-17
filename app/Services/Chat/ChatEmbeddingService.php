<?php

namespace App\Services\Chat;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

final class ChatEmbeddingService
{
    /**
     * @var array<string, array>
     * Bộ nhớ đệm tĩnh (runtime cache) ở mức độ request để tránh việc gọi lại API embedding
     * nhiều lần cho cùng một văn bản trong cùng một chu kỳ xử lý HTTP request.
     */
    private static array $runtimeCache = [];

    /**
     * Tạo vector nhúng (embedding) cho văn bản đầu vào.
     * Tự động duyệt qua danh sách các nhà cung cấp được cấu hình để chuyển đổi dự phòng (failover)
     * và quản lý xoay vòng khóa API (API key rotation) nếu một khóa bị rate-limited hoặc lỗi.
     *
     * @param string $text Văn bản cần tạo embedding
     * @param string $taskType Loại tác vụ nhúng (phục vụ cho Gemini, mặc định là RETRIEVAL_DOCUMENT)
     * @return array{values:array<int,float>,provider:string,model:string}|null
     */
    public function embed(string $text, string $taskType = 'RETRIEVAL_DOCUMENT'): ?array
    {
        $text = $this->prepareText($text);
        if ($text === '') {
            return null;
        }

        // Tạo khóa cache dựa trên văn bản và loại tác vụ
        $cacheKey = md5($text . '|' . $taskType);
        if (isset(self::$runtimeCache[$cacheKey])) {
            return self::$runtimeCache[$cacheKey];
        }

        // Lặp qua thứ tự các nhà cung cấp cấu hình (Gemini, OpenAI...)
        foreach ((array) config('chatbot.embedding.provider_order', ['gemini']) as $provider) {
            $providerConfig = (array) config("chatbot.providers.{$provider}", []);
            $keys = (array) ($providerConfig['keys'] ?? []);

            // Thử từng API key của nhà cung cấp hiện tại
            foreach ($keys as $index => $key) {
                if ($this->isCoolingDown($provider, $index)) {
                    continue;
                }

                try {
                    $result = match ($provider) {
                        'gemini' => $this->embedGemini($text, $providerConfig, (string) $key, $index, $taskType),
                        'openai' => $this->embedOpenAi($text, $providerConfig, (string) $key, $index),
                        default => null,
                    };

                    if ($result !== null) {
                        self::$runtimeCache[$cacheKey] = $result;
                    }

                    return $result;
                } catch (\Throwable $e) {
                    // Ghi nhận cảnh báo khi một API key hoặc nhà cung cấp bị lỗi
                    Log::warning('CHATBOT_EMBEDDING_PROVIDER_FAILED', [
                        'provider' => $provider,
                        'key_index' => $index,
                        'key_hash' => substr(hash('sha256', (string) $key), 0, 10),
                        'message' => $e->getMessage(),
                    ]);
                }
            }
        }

        return null;
    }

    /**
     * Gọi API của Google Gemini để tạo vector nhúng (embedding).
     *
     * @param string $text Văn bản cần tạo embedding
     * @param array<string,mixed> $providerConfig Cấu hình của nhà cung cấp Gemini
     * @param string $key API key đang sử dụng
     * @param int $keyIndex Chỉ số của API key trong cấu hình
     * @param string $taskType Loại tác vụ nhúng
     * @return array{values:array<int,float>,provider:string,model:string}
     */
    private function embedGemini(string $text, array $providerConfig, string $key, int $keyIndex, string $taskType): array
    {
        $model = (string) config('chatbot.embedding.gemini_model', 'gemini-embedding-001');
        $baseUrl = rtrim((string) ($providerConfig['base_url'] ?? 'https://generativelanguage.googleapis.com/v1beta'), '/');

        $response = Http::timeout((int) config('chatbot.embedding.timeout_seconds', 30))
            ->post("{$baseUrl}/models/{$model}:embedContent?key={$key}", [
                'model' => "models/{$model}",
                'content' => [
                    'parts' => [
                        ['text' => $text],
                    ],
                ],
                'task_type' => $taskType,
                'output_dimensionality' => (int) config('chatbot.embedding.gemini_output_dimensionality', 768),
            ]);

        $this->ensureSuccessfulResponse($response, 'gemini', $keyIndex);

        $values = data_get($response->json(), 'embedding.values', []);
        if (! is_array($values) || $values === []) {
            throw new RuntimeException('Gemini returned an empty embedding.');
        }

        return [
            'values' => $this->normalizeValues($values),
            'provider' => 'gemini',
            'model' => $model,
        ];
    }

    /**
     * Gọi API của OpenAI để tạo vector nhúng (embedding).
     *
     * @param string $text Văn bản cần tạo embedding
     * @param array<string,mixed> $providerConfig Cấu hình của nhà cung cấp OpenAI
     * @param string $key API key đang sử dụng
     * @param int $keyIndex Chỉ số của API key trong cấu hình
     * @return array{values:array<int,float>,provider:string,model:string}
     */
    private function embedOpenAi(string $text, array $providerConfig, string $key, int $keyIndex): array
    {
        $model = (string) config('chatbot.embedding.openai_model', 'text-embedding-3-small');
        $baseUrl = rtrim((string) ($providerConfig['base_url'] ?? 'https://api.openai.com/v1'), '/');

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$key}",
            'Content-Type' => 'application/json',
        ])
            ->timeout((int) config('chatbot.embedding.timeout_seconds', 30))
            ->post("{$baseUrl}/embeddings", [
                'model' => $model,
                'input' => $text,
            ]);

        $this->ensureSuccessfulResponse($response, 'openai', $keyIndex);

        $values = data_get($response->json(), 'data.0.embedding', []);
        if (! is_array($values) || $values === []) {
            throw new RuntimeException('OpenAI returned an empty embedding.');
        }

        return [
            'values' => $this->normalizeValues($values),
            'provider' => 'openai',
            'model' => $model,
        ];
    }

    /**
     * Đảm bảo phản hồi HTTP thành công. Nếu thất bại, ghi nhận trạng thái cooldown cho API key hiện tại.
     * Các mã trạng thái xác thực sai (401, 403) sẽ bị cooldown 24 giờ.
     * Các mã lỗi hệ thống hoặc rate-limit khác (429, 500...) sẽ bị cooldown tạm thời theo cấu hình.
     *
     * @param Response $response Đối tượng phản hồi HTTP
     * @param string $provider Tên nhà cung cấp dịch vụ AI
     * @param int $keyIndex Chỉ số khóa API trong cấu hình
     * @return void
     * @throws RuntimeException khi phản hồi không thành công
     */
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

        if (in_array($status, [401, 403, 429, 500, 502, 503, 504], true)) {
            Cache::put(
                "chatbot:embedding_cooldown:{$provider}:{$keyIndex}",
                true,
                in_array($status, [401, 403], true) ? 86400 : (int) config('chatbot.key_cooldown_seconds', 3600)
            );
        }

        throw new RuntimeException("{$provider} embedding HTTP {$status}: ".mb_substr($message, 0, 300));
    }

    /**
     * Làm sạch văn bản đầu vào: loại bỏ khoảng trắng dư thừa, giới hạn độ dài ký tự tối đa
     * để tránh lỗi quá tải token của nhà cung cấp API.
     *
     * @param string $text Văn bản đầu vào
     * @return string Văn bản đã được làm sạch
     */
    private function prepareText(string $text): string
    {
        $text = preg_replace('/\s+/u', ' ', trim($text));
        $text = is_string($text) ? $text : '';

        return mb_substr($text, 0, (int) config('chatbot.embedding.max_input_chars', 6000));
    }

    /**
     * Chuẩn hóa mảng giá trị số thực của vector về đúng định dạng float trong PHP.
     *
     * @param array<int,mixed> $values Mảng giá trị của vector nhúng
     * @return array<int,float> Mảng giá trị float đã chuẩn hóa
     */
    private function normalizeValues(array $values): array
    {
        return array_values(array_map(static fn (mixed $value): float => (float) $value, $values));
    }

    /**
     * Kiểm tra xem một API key cụ thể của nhà cung cấp có đang bị tạm ngưng (cooldown) hay không.
     *
     * @param string $provider Tên nhà cung cấp dịch vụ AI
     * @param int $keyIndex Chỉ số khóa API trong cấu hình
     * @return bool
     */
    private function isCoolingDown(string $provider, int $keyIndex): bool
    {
        return Cache::has("chatbot:embedding_cooldown:{$provider}:{$keyIndex}");
    }
}

<?php

namespace App\Services\Chat;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

final class ChatEmbeddingService
{
    /** @return array{values:array<int,float>,provider:string,model:string}|null */
    public function embed(string $text, string $taskType = 'RETRIEVAL_DOCUMENT'): ?array
    {
        $text = $this->prepareText($text);
        if ($text === '') {
            return null;
        }

        foreach ((array) config('chatbot.embedding.provider_order', ['gemini']) as $provider) {
            $providerConfig = (array) config("chatbot.providers.{$provider}", []);
            $keys = (array) ($providerConfig['keys'] ?? []);

            foreach ($keys as $index => $key) {
                if ($this->isCoolingDown($provider, $index)) {
                    continue;
                }

                try {
                    return match ($provider) {
                        'gemini' => $this->embedGemini($text, $providerConfig, (string) $key, $index, $taskType),
                        'openai' => $this->embedOpenAi($text, $providerConfig, (string) $key, $index),
                        default => null,
                    };
                } catch (\Throwable $e) {
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

    /** @return array{values:array<int,float>,provider:string,model:string} */
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

    /** @return array{values:array<int,float>,provider:string,model:string} */
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

    private function prepareText(string $text): string
    {
        $text = preg_replace('/\s+/u', ' ', trim($text));
        $text = is_string($text) ? $text : '';

        return mb_substr($text, 0, (int) config('chatbot.embedding.max_input_chars', 6000));
    }

    /** @param array<int,mixed> $values @return array<int,float> */
    private function normalizeValues(array $values): array
    {
        return array_values(array_map(static fn (mixed $value): float => (float) $value, $values));
    }

    private function isCoolingDown(string $provider, int $keyIndex): bool
    {
        return Cache::has("chatbot:embedding_cooldown:{$provider}:{$keyIndex}");
    }
}

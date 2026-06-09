<?php

namespace App\Services\Chat;

use App\Enums\HttpStatusCode;
use App\Models\ChatCache;
use App\Models\ChatMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class ChatService
{
    public function __construct(
        protected ChatQueryUnderstandingService $queryUnderstanding,
        protected ChatIntentGuardService $intentGuard,
        protected ChatKnowledgeSearchService $knowledgeSearch,
        protected ChatAiProviderService $aiProvider
    ) {}

    public function send(array $data, Request $request): array
    {
        if (! (bool) config('chatbot.enabled', true)) {
            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $this->responsePayload(
                    'Chatbot hiện đang tạm bảo trì. Bạn vui lòng thử lại sau.',
                    [],
                    'disabled',
                    false,
                    false
                ),
            ];
        }

        $question = $this->normalizeQuestion((string) $data['message']);
        $locale = (string) ($data['locale'] ?? 'vi');
        $sessionId = $this->resolveSessionId($request, $data['session_id'] ?? null);
        $understanding = $this->queryUnderstanding->understand($question, $locale);
        $classification = $this->intentGuard->classify((string) $understanding['normalized_question']);
        $intent = $classification['intent'];
        $isInScope = $classification['is_in_scope'];

        if (! $isInScope) {
            $answer = $this->outOfScopeAnswer($locale);
            $this->recordMessage($request, $sessionId, $question, $answer, $intent, false, false, [], [
                'reason' => $classification['reason'] ?? null,
            ]);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $this->responsePayload($answer, [], $intent, false, false),
            ];
        }

        $cacheHash = $this->cacheHash($locale, $intent, (string) $understanding['normalized_question']);
        $cached = ChatCache::query()
            ->where('question_hash', $cacheHash)
            ->where(function ($query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->first();

        if ($cached) {
            $this->recordMessage($request, $sessionId, $question, $cached->answer, $intent, true, true, [], [
                'provider' => $cached->provider,
                'model' => $cached->model,
            ]);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data' => $this->responsePayload(
                    $cached->answer,
                    $cached->recommendations ?? [],
                    $intent,
                    true,
                    true,
                    $cached->center,
                    $cached->zoom,
                    $cached->provider,
                    $cached->model,
                    0,
                    $understanding
                ),
            ];
        }

        $knowledge = $this->knowledgeSearch->search(
            $question,
            $intent,
            (int) config('chatbot.max_context_items', 5),
            $understanding
        );

        $messages = $this->buildAiMessages($question, $locale, $intent, $knowledge['context'], $understanding);
        $ai = $this->aiProvider->complete($messages);
        $answer = $ai['ok']
            ? (string) $ai['text']
            : $this->fallbackAnswer($locale, $intent, $knowledge['context']);

        $provider = $ai['provider'] ?? null;
        $model = $ai['model'] ?? null;
        $tokensUsed = (int) ($ai['tokens_used'] ?? 0);

        $this->storeCache(
            $cacheHash,
            $question,
            $locale,
            $intent,
            $answer,
            $knowledge['recommendations'],
            $knowledge['center'],
            $knowledge['zoom'],
            $provider,
            $model
        );

        $this->recordMessage($request, $sessionId, $question, $answer, $intent, true, false, $knowledge['context'], [
            'provider' => $provider,
            'model' => $model,
            'tokens_used' => $tokensUsed,
            'ai_ok' => $ai['ok'],
            'attempts' => $ai['attempts'] ?? 0,
            'understanding' => $understanding,
        ]);

        return [
            'status' => HttpStatusCode::SUCCESS->value,
            'message' => 'Chat response generated successfully.',
            'data' => $this->responsePayload(
                $answer,
                $knowledge['recommendations'],
                $intent,
                true,
                false,
                $knowledge['center'],
                $knowledge['zoom'],
                $provider,
                $model,
                $tokensUsed,
                $understanding
            ),
        ];
    }

    /** @param array<int,array<string,mixed>> $context */
    private function buildAiMessages(string $question, string $locale, string $intent, array $context, array $understanding): array
    {
        $language = $locale === 'en' ? 'English' : 'Vietnamese with full accents';

        return [
            [
                'role' => 'system',
                'content' => implode("\n", [
                    'You are DanangTrip AI, a travel assistant for Da Nang, Hoi An, Hue and nearby Central Vietnam trips.',
                    "Answer in {$language}.",
                    'Only answer questions about tours, locations, food, travel blog articles, guides, itineraries, booking, payment, refund, account and DanangTrip policies.',
                    'Use only the provided context for concrete names, prices, durations, addresses and policies.',
                    'If context is limited, say it clearly and suggest what the user can ask next.',
                    'Keep the answer concise, practical and friendly. Do not invent unavailable prices or schedules.',
                ]),
            ],
            [
                'role' => 'user',
                'content' => json_encode([
                    'intent' => $intent,
                    'question' => $question,
                    'understanding' => $understanding,
                    'context' => $context,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ],
        ];
    }

    private function fallbackAnswer(string $locale, string $intent, array $context): string
    {
        if (empty($context)) {
            return $locale === 'en'
                ? 'I could not find matching DanangTrip data for this question yet. You can ask about tours, places, food, travel articles, booking, payment or refund policies.'
                : 'Hiện mình chưa tìm thấy dữ liệu DanangTrip phù hợp cho câu hỏi này. Bạn có thể hỏi về tour, địa điểm, ăn uống, bài viết du lịch, đặt tour, thanh toán hoặc chính sách hoàn tiền.';
        }

        $titles = collect($context)
            ->pluck('title')
            ->filter()
            ->unique()
            ->take(5)
            ->values()
            ->all();

        if ($locale === 'en') {
            return "I found some relevant DanangTrip information: ".implode(', ', $titles).'. Please open the suggested cards below for details.';
        }

        return "Mình tìm thấy một số thông tin phù hợp trong DanangTrip: ".implode(', ', $titles).'. Bạn có thể xem các thẻ gợi ý bên dưới để mở chi tiết.';
    }

    private function outOfScopeAnswer(string $locale): string
    {
        if ($locale === 'en') {
            return 'I am DanangTrip travel assistant. I currently only support tours, places, travel articles, itineraries, booking, payment, refund, account and service policy questions.';
        }

        return 'Mình là trợ lý du lịch DanangTrip, hiện mình chỉ hỗ trợ thông tin về tour, địa điểm, bài viết/cẩm nang du lịch, lịch trình, đặt tour, thanh toán, hoàn tiền, tài khoản và chính sách dịch vụ. Bạn có thể hỏi: “Có tour Bà Nà nào dưới 1 triệu không?” hoặc “Gợi ý bài viết về biển”.';
    }

    private function storeCache(
        string $hash,
        string $question,
        string $locale,
        string $intent,
        string $answer,
        array $recommendations,
        ?array $center,
        ?int $zoom,
        ?string $provider,
        ?string $model
    ): void {
        try {
            ChatCache::query()->updateOrCreate(
                ['question_hash' => $hash],
                [
                    'normalized_question' => mb_substr($question, 0, 500),
                    'locale' => $locale,
                    'intent' => $intent,
                    'answer' => $answer,
                    'recommendations' => $recommendations,
                    'center' => $center,
                    'zoom' => $zoom,
                    'provider' => $provider,
                    'model' => $model,
                    'expires_at' => now()->addSeconds((int) config('chatbot.cache_ttl_seconds', 86400)),
                ]
            );
        } catch (\Throwable $e) {
            Log::warning('CHATBOT_CACHE_STORE_FAILED', ['message' => $e->getMessage()]);
        }
    }

    private function recordMessage(
        Request $request,
        string $sessionId,
        string $question,
        string $answer,
        string $intent,
        bool $isInScope,
        bool $cacheHit,
        array $context,
        array $metadata
    ): void {
        try {
            ChatMessage::query()->create([
                'user_id' => $request->user()?->id,
                'session_id' => $sessionId,
                'question' => $question,
                'answer' => $answer,
                'intent' => $intent,
                'is_in_scope' => $isInScope,
                'tokens_used' => (int) ($metadata['tokens_used'] ?? 0),
                'provider' => $metadata['provider'] ?? null,
                'model' => $metadata['model'] ?? null,
                'cache_hit' => $cacheHit,
                'context' => $context,
                'metadata' => $metadata,
                'ip_address' => $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
            ]);
        } catch (\Throwable $e) {
            Log::warning('CHATBOT_MESSAGE_STORE_FAILED', ['message' => $e->getMessage()]);
        }
    }

    private function responsePayload(
        string $answer,
        array $recommendations,
        string $intent,
        bool $isInScope,
        bool $cacheHit,
        ?array $center = null,
        ?int $zoom = null,
        ?string $provider = null,
        ?string $model = null,
        int $tokensUsed = 0,
        ?array $understanding = null
    ): array {
        return [
            'text' => $answer,
            'answer' => $answer,
            'recommendations' => $recommendations,
            'center' => $center,
            'zoom' => $zoom,
            'meta' => [
                'intent' => $intent,
                'is_in_scope' => $isInScope,
                'cache_hit' => $cacheHit,
                'provider' => $provider,
                'model' => $model,
                'tokens_used' => $tokensUsed,
                'understanding' => $understanding,
            ],
        ];
    }

    private function resolveSessionId(Request $request, ?string $sessionId): string
    {
        $sessionId = trim((string) $sessionId);
        if ($sessionId !== '') {
            return mb_substr($sessionId, 0, 100);
        }

        $raw = (string) $request->ip().'|'.(string) $request->userAgent();

        return substr(hash('sha256', $raw), 0, 64);
    }

    private function cacheHash(string $locale, string $intent, string $question): string
    {
        return hash('sha256', implode('|', [$locale, $intent, Str::lower($question)]));
    }

    private function normalizeQuestion(string $question): string
    {
        $normalized = preg_replace('/\s+/u', ' ', trim($question));

        return is_string($normalized) ? $normalized : trim($question);
    }
}

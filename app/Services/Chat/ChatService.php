<?php

namespace App\Services\Chat;

use App\Enums\HttpStatusCode;
use App\Models\ChatCache;
use App\Models\ChatMessage;
use App\Services\Chat\IntentConsistencyService;
use App\Services\Chat\ChatSessionMemoryService;
use App\Services\Chat\ChatToolGuardrailService;
use App\Services\Chat\ChatEmbeddingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class ChatService
{
    public function __construct(
        protected ChatQueryUnderstandingService    $queryUnderstanding,
        protected ChatIntentGuardService           $intentGuard,
        protected ChatKnowledgeSearchService       $knowledgeSearch,
        protected ChatAiProviderService            $aiProvider,
        protected ChatQueryNormalizerService       $normalizer,
        protected ChatRecommendationBuilderService $recommendationBuilder,
        protected IntentConsistencyService         $consistencyService,
        protected ChatSessionMemoryService         $sessionMemory,
        protected ChatToolGuardrailService         $guardrail,
        protected ChatEmbeddingService             $embeddingService
    ) {}

    public function send(array $data, Request $request): array
    {
        $startTime = microtime(true);
        $question  = $this->normalizeQuestion((string) $data['message']);
        $locale    = (string) ($data['locale'] ?? 'vi');
        $sessionId = $this->resolveSessionId($request, $data['session_id'] ?? null);
        $history   = $data['history'] ?? [];

        // Load dynamic DB settings first to ensure all thresholds, TTL, enabled state, etc. are updated
        $this->loadDbSettings();

        // === Chatbot disabled check ===
        if (! (bool) config('chatbot.enabled', true)) {
            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data'   => $this->responsePayload(
                    'Chatbot hiện đang tạm bảo trì. Bạn vui lòng thử lại sau.',
                    [], 'disabled', false, false
                ),
            ];
        }

        $knowledgeVersion = $this->getKnowledgeVersion();

        // =====================================================================
        // BƯỚC 1: Rule-based NLU (luôn chạy, nhanh, không tốn API)
        // =====================================================================
        $understanding = $this->queryUnderstanding->understand($question, $locale);

        // =====================================================================
        // BƯỚC 2: Intent Guard (dùng normalized_question sau rule-based)
        // =====================================================================
        $classification = $this->intentGuard->classify((string) $understanding['normalized_question']);
        $intent         = $classification['intent'];
        $isInScope      = $classification['is_in_scope'];

        if (! $isInScope) {
            $answer = $this->outOfScopeAnswer($locale);
            $this->recordMessage($request, $sessionId, $question, $answer, $intent, false, false, [], [
                'reason' => $classification['reason'] ?? null,
            ], $startTime);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data'   => $this->responsePayload($answer, [], $intent, false, false),
            ];
        }

        // =====================================================================
        // BƯỚC 3: GREETING HANDLER — Fast path, bypass toàn bộ search pipeline
        // =====================================================================
        if ($intent === 'greeting') {
            $answer = $this->greetingAnswer($locale);
            $this->recordMessage($request, $sessionId, $question, $answer, $intent, true, false, [], [
                'greeting_fast_path' => true,
            ], $startTime);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data'   => $this->responsePayload($answer, [], $intent, true, false),
            ];
        }

        // =====================================================================
        // BƯỚC 3b: HANDOFF HANDLER (Rule-based detection)
        // =====================================================================
        if ($intent === 'handoff') {
            $answer = $this->handoffAnswer($locale);
            $this->recordMessage($request, $sessionId, $question, $answer, $intent, true, false, [], [
                'reason' => 'handoff_keyword_intent',
                'needs_handoff' => true,
            ], $startTime);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data'   => $this->responsePayload($answer, [], $intent, true, false),
            ];
        }

        // =====================================================================
        // BƯỚC 5: HYBRID NLU & CONSISTENCY CHECK — Kiểm tra nhất quán & Biểu quyết
        // =====================================================================
        $ruleIntent = $intent;
        $ruleConfidence = (float) ($understanding['confidence'] ?? 0.0);
        $threshold = (float) config('chatbot.nlu.confidence_threshold', 0.8);
        $aiNluTriggered = false;

        // Kiểm tra tính nhất quán của intent rule-based với các thực thể tìm được
        $isConsistent = ! $this->consistencyService->shouldForceAi($ruleIntent, $understanding);
        
        $alpha = 0.7; // Trọng số Rule-based
        $beta = 0.3;  // Trọng số AI NLU

        $needsAiNlu = in_array($ruleIntent, ['tour', 'booking', 'schedule', 'location', 'food', 'hotel', 'blog'], true);
        $reason = 'low_confidence';

        if (! $isConsistent) {
            // Ép Rule confidence về 0.0 và giao toàn quyền quyết định cho AI NLU
            $ruleConfidence = 0.0;
            $alpha = 0.0;
            $beta = 1.0;
            $needsAiNlu = true;
            $reason = 'consistency_failed';
        }

        $finalIntent = $ruleIntent;
        $aiExtracted = null;

        if ($needsAiNlu && ($ruleConfidence < $threshold || ! $isConsistent)) {
            $aiExtracted = $this->aiProvider->extractEntitiesWithAi($question, $locale, $understanding, $ruleIntent, $reason);
            $aiNluTriggered = true;

            $aiIntent = (string) ($aiExtracted['intent'] ?? '');
            $aiConfidence = (float) ($aiExtracted['confidence'] ?? 0.5);

            if ($aiIntent !== '') {
                if ($aiIntent === $ruleIntent) {
                    $finalIntent = $ruleIntent;
                } else {
                    // Tính điểm biểu quyết có trọng số
                    $ruleScore = $alpha * $ruleConfidence;
                    $aiScore = $beta * $aiConfidence;

                    if ($aiScore > $ruleScore) {
                        $finalIntent = $aiIntent;
                    } else {
                        $finalIntent = $ruleIntent;
                    }
                }
            }

            // Ghi đè thực thể từ AI
            $understanding = array_merge($understanding, $aiExtracted);
        }

        // Cập nhật lại intent cuối cùng
        $intent = $finalIntent;
        $understanding['intent'] = $intent;

        // Load session memory early to check clarification state
        $session = $this->sessionMemory->loadSession($sessionId);

        // Nếu đang trong luồng hỏi lại (clarification) và người dùng trả lời:
        // Ta giữ nguyên intent cũ (e.g. 'tour' hoặc 'booking') trừ khi người dùng chủ động đổi sang ý định hệ thống khác (greeting, handoff, v.v.)
        if ($session['clarification_step'] !== null && $session['intent'] !== null) {
            $systemIntents = ['greeting', 'handoff', 'loyalty', 'payment', 'refund', 'contact', 'account'];
            if (! in_array($intent, $systemIntents, true)) {
                $intent = $session['intent'];
                $understanding['intent'] = $intent;
            }
        }

        // Xử lý Unknown Intent (Nâng cấp 5)
        $finalConfidence = $understanding['confidence'] ?? 0.5;
        if ($aiNluTriggered && isset($aiExtracted['confidence'])) {
            $finalConfidence = (float) $aiExtracted['confidence'];
        }

        if ($intent === 'unknown' || ($aiNluTriggered && $finalConfidence < 0.4)) {
            $intent = 'unknown';
            $understanding['intent'] = 'unknown';
            $answer = $this->unknownAnswer($locale);
            $this->recordMessage($request, $sessionId, $question, $answer, $intent, true, false, [], [
                'reason' => 'unknown_intent_clarification',
                'understanding' => $understanding,
                'ai_nlu_triggered' => $aiNluTriggered,
            ], $startTime);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data'   => $this->responsePayload($answer, [], $intent, true, false, null, null, null, null, 0, $understanding, $aiNluTriggered),
            ];
        }

        // Hậu xử lý: Đảm bảo độ chính xác của bộ lọc rẻ nhất/tốt nhất từ từ khóa trực tiếp
        $normalizedQuestion = mb_strtolower(preg_replace('/\s+/u', ' ', trim($question)));
        $cheapestKeywords = ['rẻ nhất', 'giá rẻ', 'thấp nhất', 'ít tiền', 'tiết kiệm', 'cheap', 'cheapest', 'low price', 'affordable', 'budget'];
        foreach ($cheapestKeywords as $kw) {
            if (str_contains($normalizedQuestion, $kw)) {
                $understanding['cheapest_first'] = true;
                break;
            }
        }
        $bestKeywords = ['tốt nhất', 'hay nhất', 'đẹp', 'nổi bật', 'đánh giá cao', 'best', 'top', 'highly rated', 'popular', 'nổi tiếng'];
        foreach ($bestKeywords as $kw) {
            if (str_contains($normalizedQuestion, $kw)) {
                $understanding['best_first'] = true;
                break;
            }
        }

        // Soft Entity Normalization nếu intent bị thay đổi (Nâng cấp 6)
        if ($intent !== $ruleIntent) {
            $understanding = $this->queryUnderstanding->normalizeEntitiesForIntent($understanding, $intent);
        }

        // =====================================================================
        // NÂNG CẤP BƯỚC: SESSION MEMORY & CLARIFICATION FLOW
        // =====================================================================
        // Cập nhật session memory với intent và understanding hiện tại
        $session = $this->sessionMemory->updateSession($sessionId, $understanding, $intent);

        // Check group booking handoff (>50 people) or intent handoff
        $peopleCount = (int) ($session['slots']['people'] ?? $understanding['people'] ?? 0);
        if ($intent === 'handoff' || $peopleCount > 50) {
            $intent = 'handoff';
            $answer = $this->handoffAnswer($locale);
            $this->recordMessage($request, $sessionId, $question, $answer, $intent, true, false, [], [
                'reason' => $peopleCount > 50 ? 'group_booking_large' : 'handoff_intent',
                'people_count' => $peopleCount,
                'needs_handoff' => true,
            ], $startTime);

            // Clear session memory to avoid loop
            $this->sessionMemory->clearSession($sessionId);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data'   => $this->responsePayload($answer, [], $intent, true, false),
            ];
        }

        // Nếu hệ thống xác định thiếu thông tin và cần hỏi lại làm rõ
        if ($session['clarification_step'] !== null) {
            $answer = $this->getClarificationAnswer($session['clarification_step'], $locale);

            $this->recordMessage($request, $sessionId, $question, $answer, $intent, true, false, [], [
                'reason' => 'clarification_step_triggered',
                'clarification_step' => $session['clarification_step'],
                'session_slots' => $session['slots'],
                'understanding' => $understanding,
            ], $startTime);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'message' => 'Clarification requested.',
                'data'   => $this->responsePayload(
                    $answer,
                    [],
                    $intent,
                    true,
                    false,
                    null,
                    null,
                    null,
                    null,
                    0,
                    $understanding,
                    $aiNluTriggered,
                    $session['clarification_step']
                ),
            ];
        }

        // Tích lũy các slot từ Session Memory ngược lại vào understanding để phục vụ câu truy vấn
        $understanding['destination'] = $session['slots']['destination'] ?? $understanding['destination'];
        $understanding['people'] = $session['slots']['people'] ?? $understanding['people'];
        $understanding['max_price'] = $session['slots']['max_price'] ?? $understanding['max_price'];
        $understanding['date'] = $session['slots']['date'] ?? $understanding['date'];

        // =====================================================================
        // BƯỚC 5b: SEMANTIC CACHE CHECK (Sau khi NLU và Slots hoàn thành)
        // =====================================================================
        $cached = $this->lookupSemanticCache($question, $locale, $intent, $session['slots'] ?? [], $knowledgeVersion);
        if ($cached) {
            $this->recordMessage($request, $sessionId, $question, $cached->answer, $intent, true, true, [], [
                'provider' => $cached->provider,
                'model'    => $cached->model,
                'semantic_cache_hit' => true,
            ], $startTime);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'data'   => $this->responsePayload(
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
                    $understanding,
                    $aiNluTriggered
                ),
            ];
        }

        // =====================================================================
        // NÂNG CẤP BƯỚC: TOOL GUARDRAIL LAYER
        // =====================================================================
        $guardrailResult = $this->guardrail->validate($understanding);
        $understanding   = $guardrailResult['understanding'];
        $warnings        = $guardrailResult['warnings'];

        // =====================================================================
        // BƯỚC 6: QUERY NORMALIZATION — map destination_name → location_id
        // =====================================================================
        $understanding = $this->normalizer->normalize($understanding);

        // =====================================================================
        // BƯỚC 7: KNOWLEDGE SEARCH — SQL (limits: 50/50/20) + Vector (20)
        // =====================================================================
        $knowledge = $this->knowledgeSearch->search(
            $question,
            $intent,
            (int) config('chatbot.max_context_items', 8),
            $understanding
        );

        // =====================================================================
        // BƯỚC 8: RECOMMENDATION BUILDER — merge, rank, top N
        // =====================================================================
        $recommendations = $this->recommendationBuilder->build(
            $knowledge['sql_results'],
            $knowledge['vector_results'],
            $understanding,
            (int) config('chatbot.max_recommendations', 5)
        );

        // =====================================================================
        // BƯỚC 8b: SYNC CONTEXT — đồng bộ context với recommendations đã rank
        // Đảm bảo text AI mô tả đúng các item sẽ hiển thị trong card
        // =====================================================================
        $alignedContext = $this->knowledgeSearch->buildAlignedContext(
            $recommendations,
            $knowledge['sql_results'],
            $knowledge['vector_results'],
            $intent,
            (int) config('chatbot.max_context_items', 8) + 4
        );

        // =====================================================================
        // BƯỚC 9: RESPONSE GENERATOR — AI diễn đạt lại từ context thật
        // =====================================================================
        $messages = $this->buildAiMessages($question, $locale, $intent, $alignedContext, $understanding, $history, $warnings);
        $ai       = $this->aiProvider->complete($messages);
        $answer   = $ai['ok']
            ? (string) $ai['text']
            : $this->fallbackAnswer($locale, $intent, $alignedContext);

        $provider   = $ai['provider'] ?? null;
        $model      = $ai['model'] ?? null;
        $tokensUsed = (int) ($ai['tokens_used'] ?? 0);

        // =====================================================================
        // Cache + Record + Return
        // =====================================================================
        $cacheHash = $this->cacheHash($locale, $intent, (string) $understanding['normalized_question'], $knowledgeVersion);
        $this->storeCache(
            $cacheHash, $question, $locale, $intent, $answer,
            $recommendations, $knowledge['center'], $knowledge['zoom'],
            $provider, $model, null, $session['slots'] ?? []
        );

        $this->recordMessage($request, $sessionId, $question, $answer, $intent, true, false, $alignedContext, [
            'provider'         => $provider,
            'model'            => $model,
            'tokens_used'      => $tokensUsed,
            'ai_ok'            => $ai['ok'],
            'attempts'         => $ai['attempts'] ?? 0,
            'understanding'    => $understanding,
            'ai_nlu_triggered' => $aiNluTriggered,
        ], $startTime);

        return [
            'status'  => HttpStatusCode::SUCCESS->value,
            'message' => 'Chat response generated successfully.',
            'data'    => $this->responsePayload(
                $answer,
                $recommendations,
                $intent,
                true,
                false,
                $knowledge['center'],
                $knowledge['zoom'],
                $provider,
                $model,
                $tokensUsed,
                $understanding,
                $aiNluTriggered
            ),
        ];
    }





    // =========================================================================
    // RESPONSE GENERATOR — Prompt engineering tối ưu
    // =========================================================================

    /** @param array<int,array<string,mixed>> $context */
    private function buildAiMessages(string $question, string $locale, string $intent, array $context, array $understanding, array $history = [], array $warnings = []): array
    {
        $language = $locale === 'en' ? 'English' : 'Vietnamese with full Unicode accents (tiếng Việt đầy đủ dấu)';
        $hasContext = ! empty($context);

        $warningInstructions = [];
        if (!empty($warnings)) {
            if (in_array('past_date', $warnings, true)) {
                $warningInstructions[] = "NOTE: User requested a date in the past. We have adjusted it to search from today onwards. GENTLY notify them of this date adjustment in Vietnamese (e.g., 'Em xin phép tìm các tour khởi hành từ hôm nay trở đi nhé!').";
            }
            if (in_array('invalid_people_count', $warnings, true)) {
                $warningInstructions[] = "NOTE: User provided an invalid group/people count. GENTLY assume a general query or ask them to clarify if needed.";
            }
        }

        $systemPrompt = implode("\n", array_filter([
            "You are DanangTrip AI — a friendly, knowledgeable travel assistant for Da Nang, Hoi An, Hue and Central Vietnam.",
            "Always respond in {$language}.",
            '',
            '=== CRITICAL RULES — MUST FOLLOW ===',
            '1. ONLY use information from the [CONTEXT] section below. NEVER invent prices, names, dates, addresses, or availability.',
            '2. If context is empty or insufficient for a specific question, say honestly:',
            '   (VI): "Mình chưa tìm thấy thông tin phù hợp. Bạn có thể hỏi cụ thể hơn hoặc xem thêm tại website DanangTrip."',
            '   (EN): "I couldn\'t find matching information. You can ask more specifically or browse DanangTrip website."',
            '3. NEVER say "I cannot access the internet" or "I don\'t have real-time data" — you have the provided context.',
            '4. Provide a detailed, helpful, and descriptive response. Introduce recommended options with descriptions, prices, schedules, and highlights where applicable, ensuring the content is informative and not too brief.',
            '5. Be conversational and friendly, not robotic or overly formal.',
            '6. If recommending tours/locations/restaurants, always mention their REAL NAMES and REAL PRICES from context.',
            '7. End with a helpful call-to-action: "Xem thẻ gợi ý bên dưới để đặt tour / xem chi tiết nhé!" (when recommendations exist).',
            '',
            !empty($warningInstructions) ? '=== NOTIFICATIONS & WARNINGS (GENTLY EXPLAIN TO USER) ===' : '',
            !empty($warningInstructions) ? implode("\n", $warningInstructions) : '',
            '',
            '=== RESPONSE STRUCTURE ===',
            '• Detailed direct answer and introduction to the options',
            '• Key highlights and important details (bullet points with real data from context, e.g. schedules, prices, highlights, features)',
            '• Call-to-action (if recommendations exist)',
            '',
            $hasContext ? '=== CONTEXT (REAL DATA FROM DATABASE) ===' : '=== NOTE: No matching data found in database ===',
            $hasContext ? json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) : '',
        ]));

        $messages = [
            [
                'role'    => 'system',
                'content' => $systemPrompt,
            ],
        ];

        if (is_array($history)) {
            foreach ($history as $turn) {
                if (isset($turn['role'], $turn['content'])) {
                    $messages[] = [
                        'role'    => $turn['role'] === 'user' ? 'user' : 'assistant',
                        'content' => (string) $turn['content'],
                    ];
                }
            }
        }

        $messages[] = [
            'role'    => 'user',
            'content' => json_encode([
                'intent'       => $intent,
                'question'     => $question,
                'understanding'=> [
                    'destination'  => $understanding['destination'] ?? null,
                    'topics'       => $understanding['topics'] ?? [],
                    'keywords'     => $understanding['keywords'] ?? [],
                    'max_price'    => $understanding['max_price'] ?? null,
                    'people'       => $understanding['people'] ?? null,
                    'date'         => $understanding['date'] ?? null,
                ],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ];

        return $messages;
    }

    // =========================================================================
    // Greeting handler
    // =========================================================================

    private function greetingAnswer(string $locale): string
    {
        if ($locale === 'en') {
            return implode("\n", [
                "Hi there! 👋 I'm DanangTrip AI, your travel assistant for Da Nang and Central Vietnam.",
                '',
                "Here's what I can help you with:",
                '🏖 Find tours — Ba Na Hills, Hoi An, Cu Lao Cham...',
                '🍜 Discover local food — specialty dishes, restaurants, cafes',
                '📍 Explore attractions — beaches, check-in spots, museums',
                '🏨 Find accommodation — hotels, resorts, homestays',
                '📅 Plan itineraries — 1, 2, or 3-day travel plans',
                '🎁 Learn about reward points and vouchers',
                '',
                'What would you like to explore? 😊',
            ]);
        }

        return implode("\n", [
            'Xin chào! 👋 Mình là DanangTrip AI — trợ lý du lịch Đà Nẵng và miền Trung.',
            '',
            'Mình có thể giúp bạn:',
            '🏖 **Tìm tour** — Bà Nà Hills, Hội An, Cù Lao Chàm...',
            '🍜 **Khám phá ẩm thực** — đặc sản, nhà hàng, quán cafe',
            '📍 **Gợi ý địa điểm** — bãi biển, check-in, bảo tàng',
            '🏨 **Tìm chỗ ở** — khách sạn, resort, homestay',
            '📅 **Lập lịch trình** — 1, 2 hoặc 3 ngày',
            '🎁 **Điểm thưởng & voucher** — nhận điểm, đổi ưu đãi',
            '',
            'Bạn muốn hỏi gì nào? 😊',
        ]);
    }

    // =========================================================================
    // Fallback & Out-of-scope answers
    // =========================================================================

    private function fallbackAnswer(string $locale, string $intent, array $context): string
    {
        // Nếu là policy intent, tìm policy item trong context để trả về nội dung trực tiếp
        if (in_array($intent, ['payment', 'refund', 'loyalty', 'account', 'contact'], true)) {
            $policyItem = collect($context)->firstWhere('type', 'policy');
            if ($policyItem && ! empty($policyItem['content'])) {
                return $policyItem['content'];
            }
        }

        if (empty($context)) {
            if ($locale === 'en') {
                return match ($intent) {
                    'tour', 'booking'           => 'I couldn\'t find any tours matching your request. Please try modifying your search filters (like price range or destination).',
                    'location', 'food', 'hotel' => 'I couldn\'t find any places, restaurants or hotels matching your request. Please try another search term.',
                    'blog', 'schedule'          => 'I couldn\'t find any articles or itineraries for this topic. Please try search for another topic.',
                    default                     => 'I couldn\'t find matching DanangTrip data. You can ask about tours, places, food, travel blogs, booking, payment, refund policies, points or vouchers.',
                };
            }

            return match ($intent) {
                'tour', 'booking'           => 'Mình chưa tìm thấy tour nào phù hợp với yêu cầu của bạn. Bạn có thể thử đổi điểm đến hoặc điều chỉnh bộ lọc giá nhé.',
                'location', 'food', 'hotel' => 'Mình chưa tìm thấy địa điểm, nhà hàng hoặc chỗ ở nào phù hợp với yêu cầu. Bạn có thể thử tìm từ khóa khác xem sao.',
                'blog', 'schedule'          => 'Mình chưa tìm thấy bài viết hay lịch trình nào phù hợp. Bạn có thể thử tìm chủ đề du lịch khác nhé.',
                default                     => 'Mình chưa tìm thấy dữ liệu DanangTrip phù hợp. Bạn có thể hỏi về tour, địa điểm, ăn uống, bài viết du lịch, đặt tour, thanh toán, chính sách hoàn tiền, điểm thưởng hoặc voucher.',
            };
        }

        // Lọc các item trong context khớp với loại của intent để tránh nhầm lẫn tiêu đề
        $targetTypes = match ($intent) {
            'blog', 'schedule'          => ['blog', 'vector_blog'],
            'location', 'food', 'hotel' => ['location', 'vector_location'],
            'tour', 'booking'           => ['tour', 'vector_tour'],
            default                     => [],
        };

        $filteredContext = collect($context)->filter(function (array $item) use ($targetTypes) {
            return in_array($item['type'] ?? '', $targetTypes, true);
        });

        // Nếu không có item nào khớp loại, lấy toàn bộ context để làm dự phòng
        if ($filteredContext->isEmpty()) {
            $filteredContext = collect($context);
        }

        $titles = $filteredContext
            ->pluck('title')
            ->filter()
            ->unique()
            ->take(3)
            ->values()
            ->all();

        $titleList = implode(', ', $titles);

        if ($locale === 'en') {
            return match ($intent) {
                'location', 'food'  => "Here are some places I found for you: **{$titleList}**. Check the suggestion cards below for address, hours, and directions.",
                'hotel'             => "I found some accommodation options: **{$titleList}**. See the cards below for pricing and booking details.",
                'tour', 'booking'   => "Here are some tours that match your request: **{$titleList}**. Click the cards below to view details and book.",
                'blog', 'schedule'  => "I found some relevant travel articles: **{$titleList}**. Open the suggestion cards below to read more.",
                default             => "I found some DanangTrip results for you: **{$titleList}**. Please check the suggestion cards below for more details.",
            };
        }

        return match ($intent) {
            'location'          => "Mình tìm thấy một số địa điểm phù hợp: **{$titleList}**. Xem thẻ gợi ý bên dưới để biết địa chỉ, giờ mở cửa và đường đi nhé!",
            'food'              => "Mình tìm thấy một số quán ăn/địa điểm ẩm thực: **{$titleList}**. Xem thẻ gợi ý bên dưới để xem địa chỉ và đánh giá nhé!",
            'hotel'             => "Mình tìm thấy một số chỗ ở phù hợp: **{$titleList}**. Xem thẻ gợi ý bên dưới để xem giá phòng và đặt chỗ nhé!",
            'tour', 'booking'   => "Mình tìm thấy một số tour phù hợp với bạn: **{$titleList}**. Bấm vào thẻ gợi ý bên dưới để xem chi tiết và đặt tour nhé!",
            'blog', 'schedule'  => "Mình tìm thấy một số bài viết liên quan: **{$titleList}**. Mở thẻ gợi ý bên dưới để đọc chi tiết hơn nhé!",
            default             => "Mình tìm thấy một số thông tin phù hợp: **{$titleList}**. Bạn có thể xem các thẻ gợi ý bên dưới để xem chi tiết.",
        };
    }

    private function outOfScopeAnswer(string $locale): string
    {
        if ($locale === 'en') {
            return 'I am DanangTrip travel assistant. I currently only support tours, places, travel articles, itineraries, booking, payment, refund, account, loyalty points, vouchers and service policy questions.';
        }

        return 'Mình là trợ lý du lịch DanangTrip 🤖 Mình chỉ hỗ trợ thông tin về tour, địa điểm, bài viết du lịch, lịch trình, đặt tour, thanh toán, hoàn tiền, tài khoản, điểm thưởng và voucher. Bạn có thể hỏi: "Có tour Bà Nà nào dưới 1 triệu không?" hoặc "Ăn gì ở Đà Nẵng?"';
    }

    private function unknownAnswer(string $locale): string
    {
        if ($locale === 'en') {
            return implode("\n", [
                "I didn't quite understand your request 😅",
                "Are you looking for a tour, food spots, hotels, or travel blogs? Please share more details so I can assist you better! 😊",
            ]);
        }

        return implode("\n", [
            "Mình chưa hiểu rõ ý bạn lắm 😅",
            "Bạn đang muốn tìm tour du lịch, khám phá địa điểm ăn uống, tìm khách sạn hay muốn đọc cẩm nang du lịch? Hãy chia sẻ thêm để mình hỗ trợ tốt nhất nhé! 😊",
        ]);
    }

    // =========================================================================
    // Cache
    // =========================================================================

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
        ?string $model,
        ?array $embedding = null,
        ?array $slots = null
    ): void {
        try {
            if ($embedding === null) {
                try {
                    $embedResult = $this->embeddingService->embed($question, 'RETRIEVAL_QUERY');
                    if ($embedResult && !empty($embedResult['values'])) {
                        $embedding = $embedResult['values'];
                    }
                } catch (\Throwable $e) {
                    Log::warning('CHATBOT_EMBED_FOR_CACHE_FAILED', ['message' => $e->getMessage()]);
                }
            }

            ChatCache::query()->updateOrCreate(
                ['question_hash' => $hash],
                [
                    'normalized_question' => mb_substr($question, 0, 500),
                    'locale'              => $locale,
                    'intent'              => $intent,
                    'answer'              => $answer,
                    'recommendations'     => $recommendations,
                    'suggested_questions' => [],
                    'embedding'           => $embedding,
                    'slots'               => $slots,
                    'center'              => $center,
                    'zoom'                => $zoom,
                    'provider'            => $provider,
                    'model'               => $model,
                    'expires_at'          => now()->addSeconds((int) config('chatbot.cache_ttl_seconds', 86400)),
                ]
            );
        } catch (\Throwable $e) {
            Log::warning('CHATBOT_CACHE_STORE_FAILED', ['message' => $e->getMessage()]);
        }
    }

    // =========================================================================
    // Message logging
    // =========================================================================

    private function recordMessage(
        Request $request,
        string $sessionId,
        string $question,
        string $answer,
        string $intent,
        bool $isInScope,
        bool $cacheHit,
        array $context,
        array $metadata,
        ?float $startTime = null
    ): void {
        try {
            if ($startTime !== null) {
                $metadata['latency_ms'] = (int) ((microtime(true) - $startTime) * 1000);
            }

            ChatMessage::query()->create([
                'user_id'    => $request->user()?->id,
                'session_id' => $sessionId,
                'question'   => $question,
                'answer'     => $answer,
                'intent'     => $intent,
                'is_in_scope'=> $isInScope,
                'tokens_used'=> (int) ($metadata['tokens_used'] ?? 0),
                'provider'   => $metadata['provider'] ?? null,
                'model'      => $metadata['model'] ?? null,
                'cache_hit'  => $cacheHit,
                'context'    => $context,
                'metadata'   => $metadata,
                'ip_address' => $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
            ]);
        } catch (\Throwable $e) {
            Log::warning('CHATBOT_MESSAGE_STORE_FAILED', ['message' => $e->getMessage()]);
        }
    }

    // =========================================================================
    // Response payload
    // =========================================================================

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
        ?array $understanding = null,
        bool $aiNluTriggered = false,
        ?string $clarificationStep = null
    ): array {
        $locale = request()->input('locale', 'vi');
        $suggestedQuestions = $this->generateSuggestedQuestions($intent, $locale, $understanding ?? []);

        return [
            'text'                => $answer,
            'answer'              => $answer,
            'recommendations'     => $recommendations,
            'suggested_questions' => $suggestedQuestions,
            'center'              => $center,
            'zoom'                => $zoom,
            'meta'                => [
                'intent'             => $intent,
                'is_in_scope'        => $isInScope,
                'cache_hit'          => $cacheHit,
                'ai_nlu_triggered'   => $aiNluTriggered,
                'provider'           => $provider,
                'model'              => $model,
                'tokens_used'        => $tokensUsed,
                'understanding'      => $understanding,
                'clarification_step' => $clarificationStep,
            ],
        ];
    }

    // =========================================================================
    // Utilities
    // =========================================================================

    private function resolveSessionId(Request $request, ?string $sessionId): string
    {
        $sessionId = trim((string) $sessionId);
        if ($sessionId !== '') {
            return mb_substr($sessionId, 0, 100);
        }

        $raw = (string) $request->ip() . '|' . (string) $request->userAgent();

        return substr(hash('sha256', $raw), 0, 64);
    }

    private function cacheHash(string $locale, string $intent, string $question, string $version): string
    {
        return hash('sha256', implode('|', [$locale, $intent, Str::lower($question), $version]));
    }

    private function lookupSemanticCache(
        string $question,
        string $locale,
        string $intent,
        array $slots,
        string $knowledgeVersion
    ): ?ChatCache {
        $exactHash = $this->cacheHash($locale, $intent, $question, $knowledgeVersion);
        $cached = ChatCache::query()
            ->where('question_hash', $exactHash)
            ->where(function ($query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->first();

        if ($cached) {
            // For transactional intents, we still check slots
            if (in_array($intent, ['tour', 'booking', 'schedule'], true)) {
                if ($this->slotsMatch($slots, $cached->slots)) {
                    return $cached;
                }
            } else {
                return $cached;
            }
        }

        // If not found or slots mismatch, try Semantic vector match
        try {
            $embedResult = $this->embeddingService->embed($question, 'RETRIEVAL_QUERY');
            if (!$embedResult || empty($embedResult['values'])) {
                return null;
            }
            $queryVector = $embedResult['values'];
        } catch (\Throwable $e) {
            return null;
        }

        $threshold = in_array($intent, ['tour', 'booking', 'schedule'], true)
            ? (float) config('chatbot.cache.threshold_transactional', 0.97)
            : (float) config('chatbot.cache.threshold_faq', 0.92);

        $candidates = ChatCache::query()
            ->where('locale', $locale)
            ->where('intent', $intent)
            ->whereNotNull('embedding')
            ->where(function ($query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->get();

        $bestCandidate = null;
        $bestScore = 0.0;

        foreach ($candidates as $candidate) {
            $score = $this->cosineSimilarity($queryVector, (array) $candidate->embedding);
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestCandidate = $candidate;
            }
        }

        if ($bestScore >= $threshold && $bestCandidate) {
            // Check slots for transactional intents
            if (in_array($intent, ['tour', 'booking', 'schedule'], true)) {
                if ($this->slotsMatch($slots, $bestCandidate->slots)) {
                    return $bestCandidate;
                }
            } else {
                return $bestCandidate;
            }
        }

        return null;
    }

    private function slotsMatch(array $current, ?array $cached): bool
    {
        $cached = $cached ?? [];
        foreach (['destination', 'people', 'date'] as $key) {
            $currVal = $current[$key] ?? null;
            $cachedVal = $cached[$key] ?? null;
            if ($currVal != $cachedVal) {
                return false;
            }
        }
        return true;
    }

    /** @param array<int,float|int|string> $a @param array<int,float|int|string> $b */
    private function cosineSimilarity(array $a, array $b): float
    {
        $count = min(count($a), count($b));
        if ($count === 0) {
            return 0.0;
        }

        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        for ($index = 0; $index < $count; $index++) {
            $left = (float) $a[$index];
            $right = (float) $b[$index];
            $dot += $left * $right;
            $normA += $left * $left;
            $normB += $right * $right;
        }

        if ($normA <= 0.0 || $normB <= 0.0) {
            return 0.0;
        }

        return $dot / (sqrt($normA) * sqrt($normB));
    }

    private function getKnowledgeVersion(): string
    {
        try {
            return \Illuminate\Support\Facades\Cache::remember('chatbot_knowledge_version', 60, function () {
                $tourMax = \Illuminate\Support\Facades\DB::table('tours')->max('updated_at') ?? '0';
                $locMax = \Illuminate\Support\Facades\DB::table('locations')->max('updated_at') ?? '0';
                $blogMax = \Illuminate\Support\Facades\DB::table('blog_posts')->max('updated_at') ?? '0';
                $kbMax = \Illuminate\Support\Facades\DB::table('chat_knowledge_bases')->max('updated_at') ?? '0';
                return md5($tourMax . '|' . $locMax . '|' . $blogMax . '|' . $kbMax);
            });
        } catch (\Throwable $e) {
            return '1.0.0';
        }
    }

    private function loadDbSettings(): void
    {
        try {
            $settings = \Illuminate\Support\Facades\Cache::remember('chatbot_db_settings', 60, function() {
                return \App\Models\Setting::query()
                    ->where('key', 'like', 'chatbot.%')
                    ->get()
                    ->pluck('cast_value', 'key')
                    ->toArray();
            });
            foreach ($settings as $key => $value) {
                config([$key => $value]);
            }
        } catch (\Throwable $e) {
            // Safe fallback
        }
    }

    private function handoffAnswer(string $locale): string
    {
        if ($locale === 'en') {
            return implode("\n", [
                "I apologize, but this request requires assistance from our customer support team. 📞",
                '',
                "Please connect with us directly via:",
                "📱 Zalo Chat: https://zalo.me/danangtrip",
                "📞 Hotline: 1900 1800",
                "✉️ Email: support@danangtrip.com",
                '',
                "A support agent has been notified and will review your conversation shortly. Thank you! 😊",
            ]);
        }

        return implode("\n", [
            "Dạ, yêu cầu này vượt quá phạm vi hỗ trợ của em. Em xin phép chuyển thông tin này cho nhân viên hỗ trợ trực tiếp hỗ trợ mình ngay ạ! 📞",
            '',
            "Bạn có thể kết nối nhanh với tụi em qua các kênh:",
            "📱 Zalo Chat: https://zalo.me/danangtrip",
            "📞 Hotline: 1900 1800",
            "✉️ Email: support@danangtrip.com",
            '',
            "Yêu cầu của bạn đã được gửi tới bộ phận chăm sóc khách hàng. Nhân viên sẽ phản hồi bạn trong giây lát ạ! 😊",
        ]);
    }

    private function normalizeQuestion(string $question): string
    {
        $normalized = preg_replace('/\s+/u', ' ', trim($question));

        return is_string($normalized) ? $normalized : trim($question);
    }

    private function getClarificationAnswer(string $step, string $locale): string
    {
        if ($locale === 'en') {
            return match ($step) {
                'destination' => "Which destination in Da Nang or Central Vietnam are you planning to visit? (e.g. Ba Na Hills, Hoi An, Hue, Dragon Bridge...)",
                'people'      => "How many people are in your group? Please let me know so I can suggest the best matching options.",
                default       => "Please provide more details so I can assist you better.",
            };
        }

        return match ($step) {
            'destination' => "Bạn dự định đi du lịch ở địa điểm nào tại Đà Nẵng/miền Trung ạ? (Ví dụ: Bà Nà Hills, Hội An, Huế, Cầu Rồng...)",
            'people'      => "Đoàn mình dự định đi khoảng bao nhiêu người để em tìm tour phù hợp nhất ạ?",
            default       => "Bạn vui lòng cung cấp thêm thông tin chi tiết nhé.",
        };
    }

    private function generateSuggestedQuestions(string $intent, string $locale, array $understanding): array
    {
        $destination = $understanding['destination'] ?? null;
        $isVi = $locale === 'vi';

        if ($intent === 'tour' || $intent === 'booking') {
            if ($destination) {
                $destName = mb_convert_case($destination, MB_CASE_TITLE, "UTF-8");
                return $isVi ? [
                    "Giá vé và tour {$destName} trọn gói là bao nhiêu?",
                    "Tour {$destName} trong ngày khởi hành từ Đà Nẵng có gì?",
                    "Lịch trình đi {$destName} chi tiết như thế nào?"
                ] : [
                    "How much is the all-inclusive {$destName} tour?",
                    "What does the {$destName} day tour from Da Nang include?",
                    "Can I see the detailed itinerary for {$destName} tour?"
                ];
            }
            return $isVi ? [
                "Tour Bà Nà Hills trọn gói nào giá rẻ nhất?",
                "Có tour đi Phố cổ Hội An trong ngày không?",
                "Các tour du lịch Đà Nẵng nào bán chạy nhất?"
            ] : [
                "Which is the cheapest all-inclusive Ba Na Hills tour?",
                "Are there any Hoi An Ancient Town day tours?",
                "What are the best-selling tours in Da Nang?"
            ];
        }

        if ($intent === 'location' || $intent === 'food' || $intent === 'hotel') {
            if ($destination) {
                $destName = mb_convert_case($destination, MB_CASE_TITLE, "UTF-8");
                return $isVi ? [
                    "Ở {$destName} có trò chơi hoặc điểm tham quan gì hay?",
                    "Có quán ăn ngon nào gần {$destName} không?",
                    "Có khách sạn nào tốt xung quanh {$destName} không?"
                ] : [
                    "What are the best things to do in {$destName}?",
                    "Are there any good dining spots near {$destName}?",
                    "What are the top hotels and accommodations around {$destName}?"
                ];
            }
            if ($intent === 'food') {
                return $isVi ? [
                    "Ăn hải sản Đà Nẵng ở đâu ngon bổ rẻ?",
                    "Các món ăn đặc sản Đà Nẵng nên thử?",
                    "Quán mì Quảng nào nổi tiếng nhất Đà Nẵng?"
                ] : [
                    "Where to eat cheap and fresh seafood in Da Nang?",
                    "Which Da Nang local dishes should I try?",
                    "What are the most famous Mi Quang spots in Da Nang?"
                ];
            }
            if ($intent === 'hotel') {
                return $isVi ? [
                    "Khách sạn nào gần biển Mỹ Khê giá tốt?",
                    "Resort 5 sao nào sang trọng nhất ở Đà Nẵng?",
                    "Có homestay giá rẻ nào ở trung tâm cho nhóm bạn không?"
                ] : [
                    "Which hotels near My Khe beach have good rates?",
                    "What are the most luxurious 5-star resorts in Da Nang?",
                    "Are there any cheap homestays in the city center for a group of friends?"
                ];
            }
            return $isVi ? [
                "Địa điểm du lịch check-in đẹp ở Đà Nẵng?",
                "Nên đi Cầu Rồng vào mấy giờ để xem phun lửa?",
                "Có các điểm tham quan miễn phí nào tại Đà Nẵng?"
            ] : [
                "Top Instagrammable photo spots in Da Nang?",
                "What time does the Dragon Bridge breathe fire?",
                "Are there any free tourist attractions in Da Nang?"
            ];
        }

        if ($intent === 'schedule') {
            return $isVi ? [
                "Lịch trình du lịch Đà Nẵng 3 ngày 2 đêm như thế nào?",
                "Lên kế hoạch đi Đà Nẵng - Hội An - Huế 4 ngày ra sao?",
                "Nên đi Bà Nà Hills vào ngày nào trong tuần?"
            ] : [
                "Can you suggest a 3-day 2-night Da Nang itinerary?",
                "How to plan a 4-day trip to Da Nang, Hoi An, and Hue?",
                "Which day of the week is best to visit Ba Na Hills?"
            ];
        }

        if ($intent === 'blog') {
            return $isVi ? [
                "Kinh nghiệm đi Bà Nà Hills tự túc mới nhất thế nào?",
                "Có cẩm nang ẩm thực Đà Nẵng từ A đến Z không?",
                "Mẹo mua quà đặc sản Đà Nẵng chất lượng là gì?"
            ] : [
                "Where can I find the latest Ba Na Hills guide for self-sufficient travelers?",
                "Is there a Da Nang food guide from A to Z?",
                "What are some tips for buying high-quality local souvenirs?"
            ];
        }

        if ($intent === 'loyalty') {
            return $isVi ? [
                "Cách đổi điểm thưởng lấy voucher giảm giá?",
                "Đăng bài đánh giá được cộng bao nhiêu điểm?",
                "Làm thế nào để kiểm tra ví điểm của tôi?"
            ] : [
                "How do I redeem reward points for discount vouchers?",
                "How many points do I get for posting a review?",
                "How can I check my loyalty points wallet?"
            ];
        }

        if ($intent === 'refund' || $intent === 'payment') {
            return $isVi ? [
                "Chính sách hủy tour trước mấy ngày để được hoàn tiền?",
                "Thanh toán QR chuyển khoản SePay mất bao lâu?",
                "Làm sao để biết giao dịch thanh toán đã thành công?"
            ] : [
                "How many days in advance should I cancel to get a refund?",
                "How long does QR payment via SePay take to verify?",
                "How do I know if my payment was successful?"
            ];
        }

        return $isVi ? [
            "Có tour du lịch Bà Nà Hills nào rẻ dưới 1 triệu không?",
            "Ăn gì ngon bổ rẻ ở Đà Nẵng?",
            "Lịch trình Đà Nẵng 3 ngày 2 đêm gợi ý thế nào?"
        ] : [
            "Are there any Ba Na Hills tours under 1 million VND?",
            "What to eat in Da Nang that is cheap and good?",
            "Can you suggest a 3-day 2-night Da Nang itinerary?"
        ];
    }
}

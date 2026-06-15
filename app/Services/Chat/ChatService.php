<?php

namespace App\Services\Chat;

use App\Enums\HttpStatusCode;
use App\Models\ChatCache;
use App\Models\ChatMessage;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class ChatService
{
    public function __construct(
        protected ChatQueryUnderstandingService $queryUnderstanding,
        protected ChatIntentGuardService $intentGuard,
        protected ChatKnowledgeSearchService $knowledgeSearch,
        protected ChatAiProviderService $aiProvider,
        protected ChatQueryNormalizerService $normalizer,
        protected ChatRecommendationBuilderService $recommendationBuilder,
        protected IntentConsistencyService $consistencyService,
        protected ChatSessionMemoryService $sessionMemory,
        protected ChatToolGuardrailService $guardrail,
        protected ChatEmbeddingService $embeddingService
    ) {}

    public function send(array $data, Request $request): array
    {
        $startTime = microtime(true);
        $question = $this->normalizeQuestion((string) $data['message']);
        $locale = (string) ($data['locale'] ?? 'vi');
        $sessionId = $this->resolveSessionId($request, $data['session_id'] ?? null);
        $history = $data['history'] ?? [];

        $trace = [
            'timestamp' => now()->toDateTimeString(),
            'session_id' => $sessionId,
            'question' => $question,
            'steps' => [],
            'error' => null,
        ];

        try {
            $this->aiProvider->clearLogs();

            // Load dynamic DB settings first to ensure all thresholds, TTL, enabled state, etc. are updated
            $this->loadDbSettings();

            // === Chatbot disabled check ===
            if (! (bool) config('chatbot.enabled', true)) {
                $ans = 'Chatbot hiện đang tạm bảo trì. Bạn vui lòng thử lại sau.';
                $trace['steps'][] = '❌ Chatbot disabled check failed.';
                $this->writeTraceToLog($trace);

                return [
                    'status' => HttpStatusCode::SUCCESS->value,
                    'data' => $this->responsePayload($ans, [], 'disabled', false, false),
                ];
            }

            $knowledgeVersion = $this->getKnowledgeVersion();

            // =====================================================================
            // BƯỚC 1: Rule-based NLU (luôn chạy, nhanh, không tốn API)
            // =====================================================================
            $understanding = $this->queryUnderstanding->understand($question, $locale);
            $trace['steps'][] = '1️⃣  NLU (Rule-based): intent='.($understanding['intent'] ?? 'null').' | destination='.($understanding['destination'] ?? 'null').' | people='.($understanding['people'] ?? 'null').' | price='.($understanding['max_price'] ?? 'null').' | location_topic='.($understanding['location_topic'] ?? 'null').' | content_type_hints='.implode(',', (array) ($understanding['content_type_hints'] ?? []));

            // =====================================================================
            // BƯỚC 2: Intent Guard (dùng normalized_question sau rule-based)
            // =====================================================================
            $classification = $this->intentGuard->classify((string) $understanding['normalized_question']);
            $intent = $classification['intent'];
            $isInScope = $classification['is_in_scope'];
            $trace['steps'][] = "2️⃣  INTENT GUARD: intent=$intent | in_scope=".($isInScope ? 'YES' : 'NO').' | reason='.($classification['reason'] ?? 'none');

            if (! $isInScope) {
                $answer = $this->outOfScopeAnswer($locale);
                $this->recordMessage($request, $sessionId, $question, $answer, $intent, false, false, [], [
                    'reason' => $classification['reason'] ?? null,
                ], $startTime);
                $trace['steps'][] = "⚠️  Out of scope answer generated: \"$answer\"";
                $this->writeTraceToLog($trace);

                return [
                    'status' => HttpStatusCode::SUCCESS->value,
                    'data' => $this->responsePayload($answer, [], $intent, false, false),
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
                $trace['steps'][] = "⚡ Fast path Greeting answered: \"$answer\"";
                $this->writeTraceToLog($trace);

                return [
                    'status' => HttpStatusCode::SUCCESS->value,
                    'data' => $this->responsePayload($answer, [], $intent, true, false),
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
                $trace['steps'][] = "⚡ Fast path Handoff answered: \"$answer\"";
                $this->writeTraceToLog($trace);

                return [
                    'status' => HttpStatusCode::SUCCESS->value,
                    'data' => $this->responsePayload($answer, [], $intent, true, false),
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
                if ($aiExtracted !== null) {
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
            $trace['steps'][] = "3️⃣  HYBRID NLU: final_intent=$intent | ai_nlu_triggered=".($aiNluTriggered ? 'YES' : 'no').' | understanding='.json_encode($understanding, JSON_UNESCAPED_UNICODE);

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
                $trace['steps'][] = '⚠️  Unknown intent fallback triggered.';
                $this->writeTraceToLog($trace);

                return [
                    'status' => HttpStatusCode::SUCCESS->value,
                    'data' => $this->responsePayload($answer, [], $intent, true, false, null, null, null, null, 0, $understanding, $aiNluTriggered),
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
            $trace['steps'][] = '4️⃣  SESSION MEMORY: slots='.json_encode($session['slots'] ?? [], JSON_UNESCAPED_UNICODE).' | clarification_step='.($session['clarification_step'] ?? 'null');

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
                $trace['steps'][] = '👋 Large group or handoff intent triggered handoff.';
                $this->writeTraceToLog($trace);

                return [
                    'status' => HttpStatusCode::SUCCESS->value,
                    'data' => $this->responsePayload($answer, [], $intent, true, false),
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
                $trace['steps'][] = '⚠️  Clarification requested for: '.$session['clarification_step']." (Bot: \"$answer\")";
                $this->writeTraceToLog($trace);

                return [
                    'status' => HttpStatusCode::SUCCESS->value,
                    'message' => 'Clarification requested.',
                    'data' => $this->responsePayload(
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
                    'model' => $cached->model,
                    'semantic_cache_hit' => true,
                ], $startTime);
                $trace['steps'][] = '🚀 Semantic cache HIT!';
                $this->writeTraceToLog($trace);

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
                        $understanding,
                        $aiNluTriggered
                    ),
                ];
            }

            // =====================================================================
            // NÂNG CẤP BƯỚC: TOOL GUARDRAIL LAYER
            // =====================================================================
            $guardrailResult = $this->guardrail->validate($understanding);
            $understanding = $guardrailResult['understanding'];
            $warnings = $guardrailResult['warnings'];

            // =====================================================================
            // BƯỚC 6: QUERY NORMALIZATION — map destination_name → location_id
            // =====================================================================
            $understanding = $this->normalizer->normalize($understanding);
            $trace['steps'][] = '5️⃣  NORMALIZER: destination_id='.($understanding['destination_id'] ?? 'null').' | slots='.json_encode($understanding, JSON_UNESCAPED_UNICODE);

            // =====================================================================
            // BƯỚC 7: KNOWLEDGE SEARCH — SQL (limits: 50/50/20) + Vector (20)
            // =====================================================================
            $knowledge = $this->knowledgeSearch->search(
                $question,
                $intent,
                (int) config('chatbot.max_context_items', 8),
                $understanding
            );
            $trace['steps'][] = '6️⃣  SEARCH: tours='.$knowledge['sql_results']['tours']->count().' | locations='.$knowledge['sql_results']['locations']->count().' | blogs='.$knowledge['sql_results']['blogs']->count().' | vector='.$knowledge['vector_results']->count();

            // =====================================================================
            // BƯỚC 8: RECOMMENDATION BUILDER — merge, rank, top N
            // =====================================================================
            $recommendations = $this->recommendationBuilder->build(
                $knowledge['sql_results'],
                $knowledge['vector_results'],
                $understanding,
                (int) config('chatbot.max_recommendations', 5)
            );
            $trace['steps'][] = '7️⃣  RECOMMENDATIONS: count='.count($recommendations).' | items='.implode(' | ', array_map(fn ($r) => ($r['data']['name'] ?? $r['data']['title'] ?? '?').' ('.$r['type'].')', $recommendations));

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
            $ai = $this->aiProvider->complete($messages);
            $answer = $ai['ok']
                ? (string) $ai['text']
                : $this->fallbackAnswer($locale, $intent, $alignedContext);

            $provider = $ai['provider'] ?? null;
            $model = $ai['model'] ?? null;
            $tokensUsed = (int) ($ai['tokens_used'] ?? 0);
            $trace['steps'][] = "8️⃣  AI PROVIDER: provider=$provider | model=$model | tokens=$tokensUsed | status=".($ai['ok'] ? 'SUCCESS' : 'FALLBACK').' | attempts='.($ai['attempts'] ?? 1);

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
                'provider' => $provider,
                'model' => $model,
                'tokens_used' => $tokensUsed,
                'ai_ok' => $ai['ok'],
                'attempts' => $ai['attempts'] ?? 0,
                'understanding' => $understanding,
                'ai_nlu_triggered' => $aiNluTriggered,
            ], $startTime);

            $trace['steps'][] = '🤖 BOT ANSWER: '.str_replace("\n", ' ', substr($answer, 0, 120)).'...';
            $this->writeTraceToLog($trace);

            return [
                'status' => HttpStatusCode::SUCCESS->value,
                'message' => 'Chat response generated successfully.',
                'data' => $this->responsePayload(
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
        } catch (\Throwable $e) {
            $trace['error'] = [
                'message' => $e->getMessage(),
                'file' => $e->getFile().':'.$e->getLine(),
                'trace' => $e->getTraceAsString(),
            ];
            $this->writeTraceToLog($trace);
            throw $e;
        }
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
        if (! empty($warnings)) {
            if (in_array('past_date', $warnings, true)) {
                $warningInstructions[] = "NOTE: User requested a date in the past. We have adjusted it to search from today onwards. GENTLY notify them of this date adjustment in Vietnamese (e.g., 'Em xin phép tìm các tour khởi hành từ hôm nay trở đi nhé!').";
            }
            if (in_array('invalid_people_count', $warnings, true)) {
                $warningInstructions[] = 'NOTE: User provided an invalid group/people count. GENTLY assume a general query or ask them to clarify if needed.';
            }
        }

        $locationsAndTours = [];
        $blogsAndArticles = [];
        $policies = [];

        foreach ($context as $item) {
            $type = $item['type'] ?? '';
            if ($type === 'policy') {
                $policies[] = $item;
            } elseif (in_array($type, ['tour', 'location', 'vector_tour', 'vector_location'], true)) {
                $locationsAndTours[] = $item;
            } else {
                $blogsAndArticles[] = $item;
            }
        }

        $contextBlock = '';
        if ($hasContext) {
            $blocks = [];
            if (! empty($locationsAndTours)) {
                $blocks[] = "=== OFFICIAL DATABASE RECORDS (LOCATIONS & TOURS) ===\n" .
                    "Only these items are official location or tour database records. The UI can display recommendation cards for these items.\n" .
                    json_encode($locationsAndTours, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            }
            if (! empty($blogsAndArticles)) {
                $blocks[] = "=== TRAVEL ARTICLES, GUIDES & BLOGS ===\n" .
                    "These are informal blog posts and articles. They may contain descriptions of travel experiences and mention names of various places or tours.\n" .
                    "WARNING: Many places or tours mentioned inside the content of these blogs DO NOT exist as database records. You MUST NEVER suggest, recommend, or mention their names in your response unless they are also listed in the \"OFFICIAL DATABASE RECORDS\" section above.\n" .
                    json_encode($blogsAndArticles, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            }
            if (! empty($policies)) {
                $blocks[] = "=== SYSTEM POLICIES ===\n" .
                    json_encode($policies, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            }
            $contextBlock = implode("\n\n", $blocks);
        }

        $systemPrompt = implode("\n", array_filter([
            'You are DanangTrip AI — a friendly, knowledgeable travel assistant for Da Nang, Hoi An, Hue and Central Vietnam.',
            "Always respond in {$language}.",
            '',
            '=== CRITICAL RULES — MUST FOLLOW ===',
            '1. ONLY use information from the CONTEXT section below. NEVER invent prices, names, dates, addresses, or availability.',
            '2. If the "=== OFFICIAL DATABASE RECORDS (LOCATIONS & TOURS) ===" section is completely empty, say honestly:',
            '   (VI): "Mình chưa tìm thấy thông tin phù hợp. Bạn có thể hỏi cụ thể hơn hoặc xem thêm tại website DanangTrip."',
            '   (EN): "I couldn\'t find matching information. You can ask more specifically or browse DanangTrip website."',
            '3. NEVER say "I cannot access the internet" or "I don\'t have real-time data" — you have the provided context.',
            '4. Provide a detailed, helpful, and descriptive response. Introduce recommended options with descriptions, prices, schedules, and highlights where applicable, ensuring the content is informative and not too brief.',
            '5. Be conversational and friendly, not robotic or overly formal.',
            '6. If recommending tours/locations/restaurants, always mention their REAL NAMES and REAL PRICES from context.',
            '7. End with a helpful call-to-action: "Xem thẻ gợi ý bên dưới để đặt tour / xem chi tiết nhé!" (when recommendations exist).',
            '8. STRICT ALIGNMENT & ALTERNATIVES RULE: You MUST ONLY recommend, list, or mention by name specific places (cafes, restaurants, hotels, spots) or tours that are explicitly listed in the "=== OFFICIAL DATABASE RECORDS (LOCATIONS & TOURS) ===" section. You MUST NEVER recommend or mention any cafes, restaurants, hotels, spots, or tours that only appear inside the text content of the "=== TRAVEL ARTICLES, GUIDES & BLOGS ===" section if they are not in the "OFFICIAL DATABASE RECORDS" section.',
            '   If the user asks for a specific category or feature (e.g. "view biển/beach view", "giá rẻ dưới 50k", etc.) and none of the items in "OFFICIAL DATABASE RECORDS" match that criteria, you MUST clearly state in Vietnamese that there are no such specific places in the database, and then immediately introduce and recommend the available items from "OFFICIAL DATABASE RECORDS" as the best alternative cafes/places. Ensure that every specific place/tour name you mention in your answer matches one of the recommendation cards shown below.',
            '',
            ! empty($warningInstructions) ? '=== NOTIFICATIONS & WARNINGS (GENTLY EXPLAIN TO USER) ===' : '',
            ! empty($warningInstructions) ? implode("\n", $warningInstructions) : '',
            '',
            '=== RESPONSE STRUCTURE ===',
            '• Detailed direct answer and introduction to the options',
            '• Key highlights and important details (bullet points with real data from context, e.g. schedules, prices, highlights, features)',
            '• Call-to-action (if recommendations exist)',
            '',
            $hasContext ? '=== CONTEXT (REAL DATA FROM DATABASE) ===' : '=== NOTE: No matching data found in database ===',
            $hasContext ? $contextBlock : '',
        ]));

        $messages = [
            [
                'role' => 'system',
                'content' => $systemPrompt,
            ],
        ];

        if (is_array($history)) {
            foreach ($history as $turn) {
                if (isset($turn['role'], $turn['content'])) {
                    $messages[] = [
                        'role' => $turn['role'] === 'user' ? 'user' : 'assistant',
                        'content' => (string) $turn['content'],
                    ];
                }
            }
        }

        // Tái tạo câu hỏi thực sự khi người dùng đang trong luồng clarification
        // (VD: bước 3 user gõ "Đoàn 3-5 người" thay vì "Tìm tour Bà Nà cho 5 người")
        $effectiveQuestion = $question;
        if (! empty($understanding['destination']) || ! empty($understanding['people'])) {
            $parts = [];
            if (! empty($understanding['destination'])) {
                $parts[] = 'điểm đến: '.$understanding['destination'];
            }
            if (! empty($understanding['people'])) {
                $parts[] = 'số người: '.$understanding['people'];
            }
            if (! empty($understanding['max_price'])) {
                $parts[] = 'giá tối đa: '.number_format((int) $understanding['max_price']).' VND';
            }
            if (! empty($understanding['date'])) {
                $parts[] = 'ngày đi: '.$understanding['date'];
            }
            if ($intent === 'tour' && ! empty($parts)) {
                $effectiveQuestion = 'Tìm '.$intent.' cho tôi với: '.implode(', ', $parts).'. Ưu tiên '.(($understanding['cheapest_first'] ?? false) ? 'giá rẻ nhất' : 'phổ biến nhất').'.';
            }
        }

        $messages[] = [
            'role' => 'user',
            'content' => json_encode([
                'intent' => $intent,
                'question' => $effectiveQuestion,
                'understanding' => [
                    'destination' => $understanding['destination'] ?? null,
                    'topics' => $understanding['topics'] ?? [],
                    'keywords' => $understanding['keywords'] ?? [],
                    'max_price' => $understanding['max_price'] ?? null,
                    'people' => $understanding['people'] ?? null,
                    'date' => $understanding['date'] ?? null,
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
                    'tour', 'booking' => 'I couldn\'t find any tours matching your request. Please try modifying your search filters (like price range or destination).',
                    'location', 'food', 'hotel' => 'I couldn\'t find any places, restaurants or hotels matching your request. Please try another search term.',
                    'blog', 'schedule' => 'I couldn\'t find any articles or itineraries for this topic. Please try search for another topic.',
                    default => 'I couldn\'t find matching DanangTrip data. You can ask about tours, places, food, travel blogs, booking, payment, refund policies, points or vouchers.',
                };
            }

            return match ($intent) {
                'tour', 'booking' => 'Mình chưa tìm thấy tour nào phù hợp với yêu cầu của bạn. Bạn có thể thử đổi điểm đến hoặc điều chỉnh bộ lọc giá nhé.',
                'location', 'food', 'hotel' => 'Mình chưa tìm thấy địa điểm, nhà hàng hoặc chỗ ở nào phù hợp với yêu cầu. Bạn có thể thử tìm từ khóa khác xem sao.',
                'blog', 'schedule' => 'Mình chưa tìm thấy bài viết hay lịch trình nào phù hợp. Bạn có thể thử tìm chủ đề du lịch khác nhé.',
                default => 'Mình chưa tìm thấy dữ liệu DanangTrip phù hợp. Bạn có thể hỏi về tour, địa điểm, ăn uống, bài viết du lịch, đặt tour, thanh toán, chính sách hoàn tiền, điểm thưởng hoặc voucher.',
            };
        }

        // Lọc các item trong context khớp với loại của intent để tránh nhầm lẫn tiêu đề
        $targetTypes = match ($intent) {
            'blog', 'schedule' => ['blog', 'vector_blog'],
            'location', 'food', 'hotel' => ['location', 'vector_location'],
            'tour', 'booking' => ['tour', 'vector_tour'],
            default => [],
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
                'location', 'food' => "Here are some places I found for you: **{$titleList}**. Check the suggestion cards below for address, hours, and directions.",
                'hotel' => "I found some accommodation options: **{$titleList}**. See the cards below for pricing and booking details.",
                'tour', 'booking' => "Here are some tours that match your request: **{$titleList}**. Click the cards below to view details and book.",
                'blog', 'schedule' => "I found some relevant travel articles: **{$titleList}**. Open the suggestion cards below to read more.",
                default => "I found some DanangTrip results for you: **{$titleList}**. Please check the suggestion cards below for more details.",
            };
        }

        return match ($intent) {
            'location' => "Mình tìm thấy một số địa điểm phù hợp: **{$titleList}**. Xem thẻ gợi ý bên dưới để biết địa chỉ, giờ mở cửa và đường đi nhé!",
            'food' => "Mình tìm thấy một số quán ăn/địa điểm ẩm thực: **{$titleList}**. Xem thẻ gợi ý bên dưới để xem địa chỉ và đánh giá nhé!",
            'hotel' => "Mình tìm thấy một số chỗ ở phù hợp: **{$titleList}**. Xem thẻ gợi ý bên dưới để xem giá phòng và đặt chỗ nhé!",
            'tour', 'booking' => "Mình tìm thấy một số tour phù hợp với bạn: **{$titleList}**. Bấm vào thẻ gợi ý bên dưới để xem chi tiết và đặt tour nhé!",
            'blog', 'schedule' => "Mình tìm thấy một số bài viết liên quan: **{$titleList}**. Mở thẻ gợi ý bên dưới để đọc chi tiết hơn nhé!",
            default => "Mình tìm thấy một số thông tin phù hợp: **{$titleList}**. Bạn có thể xem các thẻ gợi ý bên dưới để xem chi tiết.",
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
                'Are you looking for a tour, food spots, hotels, or travel blogs? Please share more details so I can assist you better! 😊',
            ]);
        }

        return implode("\n", [
            'Mình chưa hiểu rõ ý bạn lắm 😅',
            'Bạn đang muốn tìm tour du lịch, khám phá địa điểm ăn uống, tìm khách sạn hay muốn đọc cẩm nang du lịch? Hãy chia sẻ thêm để mình hỗ trợ tốt nhất nhé! 😊',
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
                    if ($embedResult && ! empty($embedResult['values'])) {
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
                    'locale' => $locale,
                    'intent' => $intent,
                    'answer' => $answer,
                    'recommendations' => $recommendations,
                    'suggested_questions' => [],
                    'embedding' => $embedding,
                    'slots' => $slots,
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
            'text' => $answer,
            'answer' => $answer,
            'recommendations' => $recommendations,
            'suggested_questions' => $suggestedQuestions,
            'center' => $center,
            'zoom' => $zoom,
            'meta' => [
                'intent' => $intent,
                'is_in_scope' => $isInScope,
                'cache_hit' => $cacheHit,
                'ai_nlu_triggered' => $aiNluTriggered,
                'provider' => $provider,
                'model' => $model,
                'tokens_used' => $tokensUsed,
                'understanding' => $understanding,
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

        $raw = (string) $request->ip().'|'.(string) $request->userAgent();

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
            if (! $embedResult || empty($embedResult['values'])) {
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
            return Cache::remember('chatbot_knowledge_version', 60, function () {
                $tourMax = DB::table('tours')->max('updated_at') ?? '0';
                $locMax = DB::table('locations')->max('updated_at') ?? '0';
                $blogMax = DB::table('blog_posts')->max('updated_at') ?? '0';
                $kbMax = DB::table('chat_knowledge_bases')->max('updated_at') ?? '0';

                return md5($tourMax.'|'.$locMax.'|'.$blogMax.'|'.$kbMax);
            });
        } catch (\Throwable $e) {
            return '1.0.0';
        }
    }

    private function loadDbSettings(): void
    {
        try {
            $settings = Cache::remember('chatbot_db_settings', 60, function () {
                return Setting::query()
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
                'I apologize, but this request requires assistance from our customer support team. 📞',
                '',
                'Please connect with us directly via:',
                '📱 Zalo Chat: https://zalo.me/danangtrip',
                '📞 Hotline: 1900 1800',
                '✉️ Email: support@danangtrip.com',
                '',
                'A support agent has been notified and will review your conversation shortly. Thank you! 😊',
            ]);
        }

        return implode("\n", [
            'Dạ, yêu cầu này vượt quá phạm vi hỗ trợ của em. Em xin phép chuyển thông tin này cho nhân viên hỗ trợ trực tiếp hỗ trợ mình ngay ạ! 📞',
            '',
            'Bạn có thể kết nối nhanh với tụi em qua các kênh:',
            '📱 Zalo Chat: https://zalo.me/danangtrip',
            '📞 Hotline: 1900 1800',
            '✉️ Email: support@danangtrip.com',
            '',
            'Yêu cầu của bạn đã được gửi tới bộ phận chăm sóc khách hàng. Nhân viên sẽ phản hồi bạn trong giây lát ạ! 😊',
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
                'destination' => 'Which destination in Da Nang or Central Vietnam are you planning to visit? (e.g. Ba Na Hills, Hoi An, Hue, Dragon Bridge...)',
                'people' => 'How many people are in your group? Please let me know so I can suggest the best matching options.',
                default => 'Please provide more details so I can assist you better.',
            };
        }

        return match ($step) {
            'destination' => 'Bạn dự định đi du lịch ở địa điểm nào tại Đà Nẵng/miền Trung ạ? (Ví dụ: Bà Nà Hills, Hội An, Huế, Cầu Rồng...)',
            'people' => 'Đoàn mình dự định đi khoảng bao nhiêu người để em tìm tour phù hợp nhất ạ?',
            default => 'Bạn vui lòng cung cấp thêm thông tin chi tiết nhé.',
        };
    }

    private function generateSuggestedQuestions(string $intent, string $locale, array $understanding): array
    {
        return [];
    }

    /**
     * Ghi dấu vết pipeline vào Laravel log
     */
    private function writeTraceToLog(array $trace): void
    {
        try {
            $content = "\n".str_repeat('═', 80)."\n";
            $content .= "📅 TIMESTAMP: {$trace['timestamp']}\n";
            $content .= "🔑 SESSION ID: {$trace['session_id']}\n";
            $content .= "📝 INPUT: \"{$trace['question']}\"\n";
            $content .= str_repeat('─', 80)."\n";

            foreach ($trace['steps'] as $step) {
                $content .= '   '.$step."\n";
            }

            // Append AI provider details/errors/key rotation attempts
            $providerLogs = $this->aiProvider->getLogs();
            if (! empty($providerLogs)) {
                $content .= "   ⚠️  AI PROVIDER LOGS:\n";
                foreach ($providerLogs as $log) {
                    $content .= '      - '.$log."\n";
                }
            }

            if ($trace['error'] !== null) {
                $content .= str_repeat('❌', 40)."\n";
                $content .= "❌ ERROR OCCURRED:\n";
                $content .= "   Message: {$trace['error']['message']}\n";
                $content .= "   File: {$trace['error']['file']}\n";
                $content .= "   Trace:\n".$trace['error']['trace']."\n";
            }

            $content .= str_repeat('═', 80)."\n";

            Log::warning('CHATBOT_PIPELINE_TRACE'.$content);

            if (app()->runningInConsole()) {
                echo $content;
            }
        } catch (\Throwable $e) {
            Log::warning('CHATBOT_TRACE_LOG_FAILED', ['message' => $e->getMessage()]);
        }
    }
}

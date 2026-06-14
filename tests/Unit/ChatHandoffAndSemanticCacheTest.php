<?php

namespace Tests\Unit;

use App\Enums\HttpStatusCode;
use App\Models\ChatCache;
use App\Services\Chat\ChatService;
use App\Services\Chat\ChatEmbeddingService;
use App\Services\Chat\ChatSessionMemoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class ChatHandoffAndSemanticCacheTest extends TestCase
{
    private ChatService $chatService;
    private ChatSessionMemoryService $sessionMemory;
    private $mockEmbeddingService;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('locations', function ($table) {
            $table->id();
            $table->string('name', 200);
            $table->string('slug', 220)->unique();
            $table->string('status', 20)->default('active');
            $table->decimal('price_min', 12, 2)->nullable();
            $table->decimal('price_max', 12, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('tours', function ($table) {
            $table->id();
            $table->string('name', 200);
            $table->string('slug', 220)->unique();
            $table->string('status', 20)->default('active');
            $table->decimal('price_adult', 12, 2)->nullable();
            $table->unsignedInteger('min_people')->default(1);
            $table->unsignedInteger('max_people')->default(0);
            $table->date('available_from')->nullable();
            $table->date('available_to')->nullable();
            $table->string('duration', 100)->nullable();
            $table->string('meeting_point', 255)->nullable();
            $table->string('short_desc', 500)->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('booking_count')->default(0);
            $table->decimal('rating_avg', 3, 2)->default(0);
            $table->boolean('is_hot')->default(false);
            $table->timestamps();
        });

        Schema::create('blog_posts', function ($table) {
            $table->id();
            $table->string('title', 200);
            $table->string('slug', 220)->unique();
            $table->string('status', 20)->default('published');
            $table->timestamps();
        });

        Schema::create('chat_cache', function ($table) {
            $table->id();
            $table->string('question_hash', 64)->unique();
            $table->string('normalized_question', 500);
            $table->string('locale', 10);
            $table->string('intent', 50);
            $table->text('answer');
            $table->json('recommendations')->nullable();
            $table->json('suggested_questions')->nullable();
            $table->json('embedding')->nullable();
            $table->json('slots')->nullable();
            $table->json('center')->nullable();
            $table->integer('zoom')->nullable();
            $table->string('provider', 50)->nullable();
            $table->string('model', 100)->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('chat_messages', function ($table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('session_id', 100);
            $table->text('question');
            $table->text('answer');
            $table->string('intent', 50);
            $table->boolean('is_in_scope')->default(true);
            $table->integer('tokens_used')->default(0);
            $table->string('provider', 50)->nullable();
            $table->string('model', 100)->nullable();
            $table->boolean('cache_hit')->default(false);
            $table->json('context')->nullable();
            $table->json('metadata')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamps();
        });

        Schema::create('chat_knowledge_base', function ($table) {
            $table->id();
            $table->string('type', 50);
            $table->unsignedBigInteger('reference_id');
            $table->string('reference_slug', 255)->nullable();
            $table->string('title', 255)->nullable();
            $table->text('content')->nullable();
            $table->string('content_hash', 64)->nullable();
            $table->text('embedding')->nullable();
            $table->string('embedding_model', 100)->nullable();
            $table->integer('embedding_dimension')->nullable();
            $table->timestamp('last_embedded_at')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Mock ChatEmbeddingService
        $this->mockEmbeddingService = $this->createMock(ChatEmbeddingService::class);
        $this->app->instance(ChatEmbeddingService::class, $this->mockEmbeddingService);

        $this->chatService = app(ChatService::class);
        $this->sessionMemory = app(ChatSessionMemoryService::class);

        config([
            'chatbot.enabled' => true,
            'chatbot.nlu.confidence_threshold' => 0.8,
            'chatbot.providers.gemini.keys' => ['mock-api-key'],
            'chatbot.provider_order' => ['gemini'],
        ]);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('locations');
        Schema::dropIfExists('tours');
        Schema::dropIfExists('blog_posts');
        Schema::dropIfExists('chat_cache');
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('chat_knowledge_base');

        parent::tearDown();
    }

    public function test_handoff_by_keywords(): void
    {
        $request = new Request();
        $response = $this->chatService->send([
            'message' => 'tôi muốn gặp nhân viên tư vấn gấp',
            'session_id' => 'handoff-session-1',
        ], $request);

        $this->assertSame(HttpStatusCode::SUCCESS->value, $response['status']);
        $this->assertSame('handoff', $response['data']['meta']['intent']);
        $this->assertStringContainsString('nhân viên hỗ trợ trực tiếp', $response['data']['text']);
    }

    public function test_handoff_by_large_group_size(): void
    {
        // Setup mock HTTP response for Gemini NLU
        Http::fake([
            '*/models/*:generateContent*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [[
                        'text' => json_encode([
                            'intent' => 'tour',
                            'confidence' => 0.95,
                            'destination' => 'Bà Nà Hills',
                            'people' => 55,
                        ])
                    ]]]
                ]]
            ])
        ]);

        $request = new Request();
        $response = $this->chatService->send([
            'message' => 'đặt tour đi Bà Nà Hills cho 55 người',
            'session_id' => 'handoff-session-2',
        ], $request);

        $this->assertSame(HttpStatusCode::SUCCESS->value, $response['status']);
        $this->assertSame('handoff', $response['data']['meta']['intent']);
        $this->assertStringContainsString('vượt quá phạm vi hỗ trợ', $response['data']['text']);
    }

    public function test_semantic_cache_hit_and_slots_verification(): void
    {
        $this->mockEmbeddingService->method('embed')
            ->willReturn(['values' => array_fill(0, 768, 0.1)]);

        // 1. Seed Cache with transactional intent and slots
        ChatCache::query()->create([
            'question_hash' => 'dummy_hash',
            'normalized_question' => 'đặt tour bà nà hills cho 3 người',
            'locale' => 'vi',
            'intent' => 'booking',
            'answer' => 'Chào bạn, đây là thông tin tour Bà Nà Hills cho 3 người.',
            'recommendations' => [],
            'suggested_questions' => [],
            'embedding' => array_fill(0, 768, 0.1),
            'slots' => [
                'destination' => 'bà nà hills',
                'people' => 3,
                'date' => null,
            ],
            'expires_at' => now()->addDay(),
        ]);

        // 2. Perform request with matching slots -> should HIT cache
        $sessionId = 'cache-session-1';
        $this->sessionMemory->clearSession($sessionId);

        // Pre-populate session slots to avoid clarification loop
        $this->sessionMemory->saveSession($sessionId, [
            'intent' => 'booking',
            'slots' => [
                'destination' => 'bà nà hills',
                'people' => 3,
                'date' => null,
            ],
            'clarification_step' => null,
            'clarification_attempts' => 0,
        ]);

        $request = new Request();
        $response = $this->chatService->send([
            'message' => 'đặt tour bà nà hills cho 3 người',
            'session_id' => $sessionId,
        ], $request);

        $this->assertSame(HttpStatusCode::SUCCESS->value, $response['status']);
        $this->assertTrue($response['data']['meta']['cache_hit']);
        $this->assertSame('Chào bạn, đây là thông tin tour Bà Nà Hills cho 3 người.', $response['data']['text']);

        // 3. Perform request with different slots (e.g. 5 people) -> should MISS cache
        $sessionId2 = 'cache-session-2';
        $this->sessionMemory->clearSession($sessionId2);

        $this->sessionMemory->saveSession($sessionId2, [
            'intent' => 'booking',
            'slots' => [
                'destination' => 'bà nà hills',
                'people' => 5,
                'date' => null,
            ],
            'clarification_step' => null,
            'clarification_attempts' => 0,
        ]);

        // Mock NLU response for NLU step
        Http::fake([
            '*/models/*:generateContent*' => Http::sequence()
                ->push([
                    'candidates' => [[
                        'content' => ['parts' => [[
                            'text' => json_encode([
                                'intent' => 'booking',
                                'confidence' => 0.95,
                                'destination' => 'Bà Nà Hills',
                                'people' => 5,
                            ])
                        ]]]
                    ]]
                ])
                ->push([
                    'candidates' => [[
                        'content' => ['parts' => [[
                            'text' => 'Câu trả lời sinh mới cho 5 người.'
                        ]]]
                    ]],
                    'usageMetadata' => ['totalTokenCount' => 100]
                ])
        ]);

        $response2 = $this->chatService->send([
            'message' => 'đặt tour bà nà hills cho 5 người',
            'session_id' => $sessionId2,
        ], $request);

        $this->assertSame(HttpStatusCode::SUCCESS->value, $response2['status']);
        $this->assertFalse($response2['data']['meta']['cache_hit']);
        $this->assertSame('Câu trả lời sinh mới cho 5 người.', $response2['data']['text']);
    }
}

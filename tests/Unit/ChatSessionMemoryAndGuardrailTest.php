<?php

namespace Tests\Unit;

use App\Enums\HttpStatusCode;
use App\Models\Tour;
use App\Services\Chat\ChatService;
use App\Services\Chat\ChatSessionMemoryService;
use App\Services\Chat\ChatToolGuardrailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class ChatSessionMemoryAndGuardrailTest extends TestCase
{
    private ChatService $chatService;

    private ChatSessionMemoryService $sessionMemory;

    private ChatToolGuardrailService $guardrail;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('locations', function ($table) {
            $table->id();
            $table->string('name', 200);
            $table->string('slug', 220)->unique();
            $table->text('description')->nullable();
            $table->string('short_description', 500)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('district', 50)->nullable();
            $table->string('ward', 50)->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->decimal('price_min', 12, 2)->nullable();
            $table->decimal('price_max', 12, 2)->nullable();
            $table->unsignedTinyInteger('price_level')->nullable();
            $table->decimal('avg_rating', 3, 2)->default(0);
            $table->unsignedInteger('review_count')->default(0);
            $table->unsignedInteger('view_count')->default(0);
            $table->unsignedInteger('favorite_count')->default(0);
            $table->string('thumbnail', 255)->nullable();
            $table->json('images')->nullable();
            $table->string('status', 20)->default('active');
            $table->boolean('is_featured')->default(false);
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
            $table->string('excerpt', 500)->nullable();
            $table->text('content')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('view_count')->default(0);
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

        $this->chatService = app(ChatService::class);
        $this->sessionMemory = app(ChatSessionMemoryService::class);
        $this->guardrail = app(ChatToolGuardrailService::class);

        config([
            'chatbot.enabled' => true,
            'chatbot.nlu.confidence_threshold' => 0.8,
            'chatbot.providers.gemini.keys' => ['mock-api-key'],
            'chatbot.provider_order' => ['gemini'],
        ]);

        Tour::query()->create([
            'name' => 'Tour Bà Nà Hills VIP',
            'slug' => 'tour-ba-na-hills-vip',
            'status' => 'active',
            'price_adult' => 1200000,
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

    public function test_session_memory_stores_and_loads_correctly(): void
    {
        $sessionId = 'session-test-123';
        $this->sessionMemory->clearSession($sessionId);

        $session = $this->sessionMemory->loadSession($sessionId);
        $this->assertNull($session['intent']);
        $this->assertNull($session['slots']['destination']);

        // Update session
        $understanding = [
            'original_question' => 'Tôi muốn đi Bà Nà Hills',
            'destination' => 'bà nà hills',
            'people' => null,
        ];
        $this->sessionMemory->updateSession($sessionId, $understanding, 'tour');

        $session = $this->sessionMemory->loadSession($sessionId);
        $this->assertSame('tour', $session['intent']);
        $this->assertSame('bà nà hills', $session['slots']['destination']);
        $this->assertSame('people', $session['clarification_step']); // since people is missing
    }

    public function test_tool_guardrail_validates_people_and_past_dates(): void
    {
        // 1. Invalid people count <= 0
        $understanding = [
            'people' => -3,
            'date' => '2026-12-30',
        ];
        $result = $this->guardrail->validate($understanding);
        $this->assertNull($result['understanding']['people']);
        $this->assertContains('invalid_people_count', $result['warnings']);

        // 2. Past date
        $understanding = [
            'people' => 4,
            'date' => '2020-01-01', // definitely in the past
        ];
        $result = $this->guardrail->validate($understanding);
        $this->assertNull($result['understanding']['date']);
        $this->assertContains('past_date', $result['warnings']);

        // 3. Price boundary swap
        $understanding = [
            'min_price' => 1000000,
            'max_price' => 500000,
        ];
        $result = $this->guardrail->validate($understanding);
        $this->assertEquals(500000, $result['understanding']['min_price']);
        $this->assertEquals(1000000, $result['understanding']['max_price']);
        $this->assertContains('price_min_greater_than_max', $result['warnings']);
    }

    public function test_chatbot_clarification_dialogue_flow(): void
    {
        $sessionId = 'conversation-flow-session';
        $this->sessionMemory->clearSession($sessionId);

        // Đăng ký chuỗi mock HTTP duy nhất cho cả cuộc hội thoại (Turn 1 và Turn 2)
        Http::fake([
            '*/models/*:generateContent*' => Http::sequence()
                ->push([ // 1. Turn 1 AI NLU extraction
                    'candidates' => [[
                        'content' => ['parts' => [[
                            'text' => json_encode([
                                'intent' => 'tour',
                                'confidence' => 0.95,
                                'destination' => 'Bà Nà Hills',
                            ]),
                        ]]],
                    ]],
                ])
                ->push([ // 2. Turn 2 AI NLU extraction
                    'candidates' => [[
                        'content' => ['parts' => [[
                            'text' => json_encode([
                                'intent' => 'tour',
                                'confidence' => 0.9,
                                'people' => 3,
                            ]),
                        ]]],
                    ]],
                ])
                ->push([ // 3. Turn 2 AI Complete (sinh câu trả lời đề xuất)
                    'candidates' => [[
                        'content' => ['parts' => [[
                            'text' => 'Đây là đề xuất tour Bà Nà Hills cho 3 người của bạn.',
                        ]]],
                    ]],
                    'usageMetadata' => ['totalTokenCount' => 150],
                ]),
        ]);

        $request = new Request;
        $response = $this->chatService->send([
            'message' => 'Tôi muốn đi Bà Nà Hills',
            'session_id' => $sessionId,
        ], $request);

        $this->assertSame(HttpStatusCode::SUCCESS->value, $response['status']);
        $this->assertStringContainsString('Đoàn mình dự định đi khoảng bao nhiêu người', $response['data']['text']);
        $this->assertSame('tour', $response['data']['meta']['intent']);

        $response2 = $this->chatService->send([
            'message' => '3 người',
            'session_id' => $sessionId,
        ], $request);

        $this->assertSame(HttpStatusCode::SUCCESS->value, $response2['status']);
        $this->assertStringContainsString('Đây là đề xuất tour Bà Nà Hills cho 3 người', $response2['data']['text']);
        $this->assertSame('tour', $response2['data']['meta']['intent']);
        $this->assertSame('Bà Nà Hills', $response2['data']['meta']['understanding']['destination']);
        $this->assertEquals(3, $response2['data']['meta']['understanding']['people']);
    }
}

<?php

namespace Tests\Unit;

use App\Enums\HttpStatusCode;
use App\Models\Location;
use App\Models\Tour;
use App\Services\Chat\ChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class ChatServiceNluTest extends TestCase
{
    private ChatService $chatService;

    protected function setUp(): void
    {
        parent::setUp();

        // Tự dựng schema rút gọn phù hợp với SQLite in-memory để tránh lỗi fulltext index và check constraints của MySQL
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

        Schema::create('categories', function ($table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('subcategories', function ($table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        $this->chatService = app(ChatService::class);

        // Bật cấu hình chatbot cho test
        config([
            'chatbot.enabled' => true,
            'chatbot.nlu.confidence_threshold' => 0.8,
            'chatbot.providers.gemini.keys' => ['mock-api-key'],
            'chatbot.provider_order' => ['gemini'],
        ]);

        // Tạo dữ liệu giả
        Location::query()->create([
            'name' => 'Cầu Rồng',
            'slug' => 'cau-rong',
            'status' => 'active',
            'address' => 'Đà Nẵng',
        ]);

        Tour::query()->create([
            'name' => 'Tour Cầu Rồng 1 ngày',
            'slug' => 'tour-cau-rong-1-ngay',
            'status' => 'active',
            'price_adult' => 500000,
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
        Schema::dropIfExists('categories');
        Schema::dropIfExists('subcategories');

        parent::tearDown();
    }

    public function test_nlu_overwrite_bug_is_fixed_when_ai_changes_intent(): void
    {
        $question = 'Tôi muốn đi Cầu Rồng tuần sau, 3 người, ngân sách dưới 1.5 triệu';

        // Fake HTTP cho cả AI NLU và AI complete
        Http::fake([
            '*/models/*:generateContent*' => Http::sequence()
                ->push([ // extractEntitiesWithAi
                    'candidates' => [[
                        'content' => ['parts' => [[
                            'text' => json_encode([
                                'intent' => 'tour',
                                'confidence' => 0.9,
                                'destination' => 'cầu rồng',
                                'people' => 3,
                                'max_price' => 1500000,
                            ]),
                        ]]],
                    ]],
                    'usageMetadata' => ['totalTokenCount' => 100],
                ])
                ->push([ // complete()
                    'candidates' => [[
                        'content' => ['parts' => [[
                            'text' => 'Đây là câu trả lời mock cho tour Cầu Rồng.',
                        ]]],
                    ]],
                    'usageMetadata' => ['totalTokenCount' => 120],
                ]),
        ]);

        $request = new Request;
        $response = $this->chatService->send([
            'message' => $question,
            'session_id' => 'test-session',
        ], $request);

        $this->assertSame(HttpStatusCode::SUCCESS->value, $response['status']);
        $this->assertSame('tour', $response['data']['meta']['intent']);
        $this->assertTrue($response['data']['meta']['ai_nlu_triggered']);
        $this->assertStringContainsString('Đây là câu trả lời mock cho tour Cầu Rồng.', $response['data']['text']);
    }

    public function test_nlu_unknown_intent_clarification_response(): void
    {
        $question = 'Tôi muốn tổ chức team building cho công ty khoảng 200 người';

        Http::fake([
            '*/models/*:generateContent*' => Http::sequence()
                ->push([ // AI NLU trả về unknown
                    'candidates' => [[
                        'content' => ['parts' => [[
                            'text' => json_encode([
                                'intent' => 'unknown',
                                'confidence' => 0.95,
                            ]),
                        ]]],
                    ]],
                ]),
        ]);

        $request = new Request;
        $response = $this->chatService->send([
            'message' => $question,
            'session_id' => 'test-session',
        ], $request);

        $this->assertSame(HttpStatusCode::SUCCESS->value, $response['status']);
        $this->assertSame('unknown', $response['data']['meta']['intent']);
        $this->assertStringContainsString('Mình chưa hiểu rõ ý bạn lắm', $response['data']['text']);
    }

    public function test_nlu_weighted_voting_rule_wins(): void
    {
        config(['chatbot.nlu.confidence_threshold' => 0.95]);

        // Câu hỏi: "Ăn hải sản Đà Nẵng cho 4 người giá dưới 2 triệu"
        // Bóc được: region (35), price (25), people (20) -> confidence = 80 / 100 = 0.8
        $question = 'Ăn hải sản Đà Nẵng cho 4 người giá dưới 2 triệu';

        Http::fake([
            '*/models/*:generateContent*' => Http::sequence()
                ->push([ // AI NLU trả về tour (ảo giác) với confidence cực cao 0.99
                    'candidates' => [[
                        'content' => ['parts' => [[
                            'text' => json_encode([
                                'intent' => 'tour',
                                'confidence' => 0.99,
                            ]),
                        ]]],
                    ]],
                    'usageMetadata' => ['totalTokenCount' => 100],
                ])
                ->push([ // complete
                    'candidates' => [[
                        'content' => ['parts' => [[
                            'text' => 'Câu trả lời mock ẩm thực.',
                        ]]],
                    ]],
                    'usageMetadata' => ['totalTokenCount' => 120],
                ]),
        ]);

        $request = new Request;
        $response = $this->chatService->send([
            'message' => $question,
            'session_id' => 'test-session',
        ], $request);

        // Intent cuối cùng vẫn phải là 'food' (rule thắng) chứ không phải 'tour' (AI ảo giác)
        $this->assertSame('food', $response['data']['meta']['intent']);
    }
}

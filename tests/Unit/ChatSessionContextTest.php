<?php

namespace Tests\Unit;

use App\Services\Chat\ChatSessionMemoryService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

final class ChatSessionContextTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['cache.default' => 'array']);
        Cache::flush();
    }

    public function test_page_context_switch_clears_previous_tour_clarification(): void
    {
        $service = app(ChatSessionMemoryService::class);
        $sessionId = 'context-switch-without-database';

        $service->updateSession(
            $sessionId,
            [
                'original_question' => 'Tìm tour Bà Nà',
                'destination' => 'bà nà hills',
                'people' => null,
            ],
            'tour',
            [
                'page_type' => 'tour_detail',
                'entity_type' => 'tour',
                'entity_slug' => 'tour-ba-na',
            ]
        );

        $session = $service->updateSession(
            $sessionId,
            [
                'original_question' => 'Đọc bài viết này',
                'destination' => null,
                'people' => null,
            ],
            'blog',
            [
                'page_type' => 'blog_detail',
                'entity_type' => 'blog',
                'entity_slug' => 'kinh-nghiem-ba-na',
            ]
        );

        $this->assertSame('blog', $session['intent']);
        $this->assertNull($session['slots']['destination']);
        $this->assertNull($session['clarification_step']);
        $this->assertSame(
            'blog_detail:blog::kinh-nghiem-ba-na',
            $session['context_signature']
        );
    }

    public function test_explicit_location_intent_exits_stale_tour_clarification_on_same_page(): void
    {
        $service = app(ChatSessionMemoryService::class);
        $sessionId = 'stale-tour-on-location-page';
        $locationContext = [
            'page_type' => 'location_detail',
            'entity_type' => 'location',
            'entity_slug' => 'ba-na-hills',
        ];

        // Mô phỏng session cũ đã bị nhận sai thành tour ngay trên trang địa điểm.
        $service->updateSession(
            $sessionId,
            [
                'original_question' => 'Bà Nà Hills',
                'destination' => 'bà nà hills',
                'people' => null,
            ],
            'tour',
            $locationContext
        );

        $session = $service->updateSession(
            $sessionId,
            [
                'original_question' => 'Tôi cần thông tin về địa điểm Bà Nà Hills',
                'destination' => 'bà nà hills',
                'people' => null,
            ],
            'location',
            $locationContext
        );

        $this->assertSame('location', $session['intent']);
        $this->assertNull($session['clarification_step']);
        $this->assertNull($session['slots']['people']);
    }
}

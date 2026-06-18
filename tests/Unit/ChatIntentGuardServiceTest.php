<?php

namespace Tests\Unit;

use App\Services\Chat\ChatIntentGuardService;
use PHPUnit\Framework\TestCase;

final class ChatIntentGuardServiceTest extends TestCase
{
    public function test_loyalty_questions_are_in_scope(): void
    {
        $service = new ChatIntentGuardService;

        foreach ([
            'Tôi có thể nhận điểm thưởng như thế nào?',
            'Đánh giá hữu ích có được cộng điểm không?',
            'Làm sao đổi point thành voucher?',
            'Mã giảm giá từ ví điểm dùng thế nào?',
        ] as $question) {
            $result = $service->classify($question);

            $this->assertTrue($result['is_in_scope'], $question);
            $this->assertSame('loyalty', $result['intent'], $question);
        }
    }

    public function test_destination_name_does_not_force_tour_intent(): void
    {
        $service = new ChatIntentGuardService;

        $result = $service->classify('Bà Nà Hills');

        $this->assertSame('unknown', $result['intent']);
        $this->assertSame('no_intent_signal', $result['reason']);
    }

    public function test_page_context_resolves_destination_only_question(): void
    {
        $service = new ChatIntentGuardService;

        $locationResult = $service->classify('Bà Nà Hills', [
            'page_type' => 'location_detail',
            'entity_type' => 'location',
        ]);
        $tourResult = $service->classify('Bà Nà Hills', [
            'page_type' => 'tour_detail',
            'entity_type' => 'tour',
        ]);

        $this->assertSame('location', $locationResult['intent']);
        $this->assertSame('page_context_only', $locationResult['reason']);
        $this->assertSame('tour', $tourResult['intent']);
        $this->assertSame('page_context_only', $tourResult['reason']);
    }

    public function test_explicit_tour_keyword_overrides_location_page_context(): void
    {
        $service = new ChatIntentGuardService;

        $result = $service->classify('Tour đi Bà Nà', [
            'page_type' => 'location_detail',
            'entity_type' => 'location',
        ]);

        $this->assertSame('tour', $result['intent']);
        $this->assertTrue($result['explicit_match']);
    }

    public function test_explicit_location_keyword_overrides_tour_page_context(): void
    {
        $service = new ChatIntentGuardService;

        $result = $service->classify('Địa điểm check-in ở Bà Nà Hills', [
            'page_type' => 'tour_detail',
            'entity_type' => 'tour',
        ]);

        $this->assertSame('location', $result['intent']);
    }

    public function test_blog_action_wins_over_tour_content_word(): void
    {
        $service = new ChatIntentGuardService;

        $result = $service->classify('Cho tôi đọc bài viết về tour Bà Nà');

        $this->assertSame('blog', $result['intent']);
    }

    public function test_booking_action_wins_over_tour_content_word(): void
    {
        $service = new ChatIntentGuardService;

        $result = $service->classify('Tôi muốn đặt tour Bà Nà cho gia đình');

        $this->assertSame('booking', $result['intent']);
    }

    public function test_handoff_request_to_meet_consultant_is_not_ambiguous_contact(): void
    {
        $service = new ChatIntentGuardService;

        foreach ([
            'Cho tôi gặp nhân viên tư vấn',
            'cho toi gap nhan vien tu van',
            'Tôi muốn gặp tư vấn viên',
        ] as $question) {
            $result = $service->classify($question);

            $this->assertSame('handoff', $result['intent'], $question);
            $this->assertTrue($result['is_in_scope'], $question);
            $this->assertTrue($result['explicit_match'], $question);
        }
    }
}

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
}

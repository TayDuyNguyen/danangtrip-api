<?php

namespace Tests\Unit;

use App\Services\Chat\ChatQueryUnderstandingService;
use Tests\TestCase;

final class ChatQueryUnderstandingServiceTest extends TestCase
{
    public function test_it_extracts_beach_topic_and_da_nang_region(): void
    {
        $result = (new ChatQueryUnderstandingService)->understand('Bãi biển đẹp ở Đà Nẵng');

        $this->assertSame('beach', $result['location_topic']);
        $this->assertSame('đà nẵng', $result['region']);
        $this->assertTrue($result['best_first']);
    }

    public function test_it_understands_common_beach_phrases(): void
    {
        $service = new ChatQueryUnderstandingService;

        foreach (['đi tắm biển', 'địa điểm ven biển', 'beautiful beach'] as $question) {
            $this->assertSame('beach', $service->understand($question)['location_topic'], $question);
        }
    }
}

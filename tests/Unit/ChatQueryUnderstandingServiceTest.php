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

    public function test_normalize_entities_for_intent(): void
    {
        $service = new ChatQueryUnderstandingService;

        $input = [
            'original_question' => 'Tôi muốn đi Cầu Rồng tuần sau, 3 người, ngân sách 1.5 triệu',
            'normalized_question' => 'tôi muốn đi cầu rồng tuần sau 3 người ngân sách 1.5 triệu',
            'destination' => 'cầu rồng',
            'region' => 'đà nẵng',
            'location_topic' => 'food',
            'max_price' => 1500000,
            'min_price' => null,
            'people' => 3,
            'date' => '2026-06-21',
            'duration_days' => 3,
            'topics' => ['local_food'],
            'topic_hints' => ['local_food'],
            'content_types' => ['tour'],
            'content_type_hints' => ['tour'],
            'confidence' => 0.8,
            'keywords' => ['cầu rồng'],
        ];

        // 1. Tour và Booking giữ nguyên tất cả
        $tourNormalized = $service->normalizeEntitiesForIntent($input, 'tour');
        $this->assertSame(3, $tourNormalized['people']);
        $this->assertSame(1500000, $tourNormalized['max_price']);
        $this->assertSame('food', $tourNormalized['location_topic']);

        // 2. Schedule xóa location_topic, topics... giữ lại destination, region, duration_days, date, people, max_price, min_price
        $scheduleNormalized = $service->normalizeEntitiesForIntent($input, 'schedule');
        $this->assertSame('cầu rồng', $scheduleNormalized['destination']);
        $this->assertSame(3, $scheduleNormalized['people']);
        $this->assertSame(1500000, $scheduleNormalized['max_price']);
        $this->assertNull($scheduleNormalized['location_topic']);
        $this->assertEmpty($scheduleNormalized['topics']);

        // 3. Location xóa duration_days, topics... giữ lại destination, region, location_topic, people, date, max_price, min_price
        $locationNormalized = $service->normalizeEntitiesForIntent($input, 'location');
        $this->assertSame('cầu rồng', $locationNormalized['destination']);
        $this->assertSame('food', $locationNormalized['location_topic']);
        $this->assertNull($locationNormalized['duration_days']);
        $this->assertEmpty($locationNormalized['topics']);

        // 4. Blog xóa people, price, duration_days, location_topic... giữ lại destination, region, topics, topic_hints
        $blogNormalized = $service->normalizeEntitiesForIntent($input, 'blog');
        $this->assertSame('cầu rồng', $blogNormalized['destination']);
        $this->assertNull($blogNormalized['people']);
        $this->assertNull($blogNormalized['max_price']);
        $this->assertNull($blogNormalized['location_topic']);
        $this->assertSame(['local_food'], $blogNormalized['topics']);
    }
}

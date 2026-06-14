<?php

namespace Tests\Unit;

use App\Services\Chat\IntentConsistencyService;
use Tests\TestCase;

final class IntentConsistencyServiceTest extends TestCase
{
    private IntentConsistencyService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new IntentConsistencyService;
    }

    public function test_it_returns_false_when_intent_has_no_config(): void
    {
        $this->assertFalse(
            $this->service->shouldForceAi('food', ['people' => 3, 'max_price' => 1000000])
        );
    }

    public function test_location_intent_with_below_threshold_abnormal_entities_returns_false(): void
    {
        // Chỉ có 1 dấu hiệu bất thường (people), threshold = 2
        $this->assertFalse(
            $this->service->shouldForceAi('location', [
                'people' => 3,
                'max_price' => null,
                'min_price' => null,
                'duration_days' => null,
            ])
        );
    }

    public function test_location_intent_with_meeting_threshold_abnormal_entities_returns_true(): void
    {
        // Có 2 dấu hiệu bất thường (people, max_price), threshold = 2
        $this->assertTrue(
            $this->service->shouldForceAi('location', [
                'people' => 3,
                'max_price' => 1500000,
                'min_price' => null,
                'duration_days' => null,
            ])
        );
    }

    public function test_blog_intent_with_one_abnormal_entity_returns_true(): void
    {
        // Có 1 dấu hiệu bất thường (max_price), threshold = 1
        $this->assertTrue(
            $this->service->shouldForceAi('blog', [
                'people' => null,
                'max_price' => 1000000,
                'min_price' => null,
                'duration_days' => null,
            ])
        );
    }
}

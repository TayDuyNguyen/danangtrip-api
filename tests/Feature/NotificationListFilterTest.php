<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use Tests\TestCase;

class NotificationListFilterTest extends TestCase
{
    /**
     * Axios sends is_read=false as the string "false" in query strings.
     * Laravel's boolean rule only accepts 0/1 unless normalized first.
     */
    public function test_list_accepts_is_read_false_query_string(): void
    {
        $user = User::query()->first();

        if (! $user) {
            $this->markTestSkipped('No users available in the connected database.');
        }

        Notification::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'type' => 'promotion',
                'title' => 'Unread filter regression',
            ],
            [
                'content' => 'Regression notification for unread list filter.',
                'is_read' => false,
                'created_at' => now(),
            ]
        );

        $this->actingAs($user);

        $response = $this->getJson('/api/v1/user/notifications?page=1&per_page=10&is_read=false');

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonStructure(['data' => ['data', 'current_page', 'last_page']]);
    }
}

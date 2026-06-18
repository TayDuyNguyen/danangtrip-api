<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use App\Repositories\Interfaces\NotificationRepositoryInterface;
use Tests\TestCase;

class NotificationUnreadCountTest extends TestCase
{
    /**
     * Regression: PostgreSQL rejects `boolean = 0` when Laravel binds false as integer.
     */
    public function test_get_unread_count_uses_pgsql_boolean_syntax_when_driver_is_pgsql(): void
    {
        if (config('database.default') !== 'pgsql') {
            $this->markTestSkipped('PostgreSQL connection required for driver-specific assertion.');
        }

        $user = User::query()->first();

        if (! $user) {
            $this->markTestSkipped('No users available in the connected database.');
        }

        $count = app(NotificationRepositoryInterface::class)->getUnreadCount($user->id);

        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }

    public function test_unread_count_endpoint_returns_success_for_authenticated_user(): void
    {
        if (config('database.default') !== 'pgsql') {
            $this->markTestSkipped('PostgreSQL connection required for driver-specific assertion.');
        }

        $user = User::query()->first();

        if (! $user) {
            $this->markTestSkipped('No users available in the connected database.');
        }

        Notification::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'type' => 'promotion',
                'title' => 'Unread count regression',
                'content' => 'Regression notification for unread count endpoint.',
            ],
            [
                'is_read' => false,
                'created_at' => now(),
            ]
        );

        $this->actingAs($user);

        $response = $this->getJson('/api/v1/user/notifications/unread-count');

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonStructure(['data' => ['unread_count']]);
    }
}

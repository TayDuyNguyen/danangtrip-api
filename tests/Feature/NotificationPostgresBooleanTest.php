<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use Tests\TestCase;

class NotificationPostgresBooleanTest extends TestCase
{
    /**
     * Regression: PostgreSQL rejects integer bindings for boolean columns on insert.
     */
    public function test_notification_insert_accepts_is_read_false_on_pgsql(): void
    {
        if (config('database.default') !== 'pgsql') {
            $this->markTestSkipped('PostgreSQL connection required for driver-specific assertion.');
        }

        $user = User::query()->first();
        if (! $user) {
            $this->markTestSkipped('No users available in the connected database.');
        }

        $notification = Notification::query()->create([
            'user_id' => $user->id,
            'type' => 'promotion',
            'title' => 'Postgres boolean regression',
            'content' => 'Ensures is_read=false inserts safely on PostgreSQL.',
            'is_read' => false,
            'created_at' => now(),
        ]);

        $this->assertFalse($notification->is_read);
        $notification->delete();
    }
}

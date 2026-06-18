<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use App\Repositories\Interfaces\NotificationRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class NotificationMarkAsReadTest extends TestCase
{
    public function test_mark_notification_as_read_updates_boolean_column_on_pgsql(): void
    {
        if (config('database.default') !== 'pgsql') {
            $this->markTestSkipped('PostgreSQL connection required for driver-specific assertion.');
        }

        $user = User::query()->first();
        if (! $user) {
            $this->markTestSkipped('No users available in the connected database.');
        }

        $notification = Notification::query()
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->first();

        if (! $notification) {
            $this->markTestSkipped('No notifications available for the selected user.');
        }

        DB::table('notifications')->where('id', $notification->id)->update([
            'is_read' => DB::raw('false'),
            'read_at' => null,
        ]);

        $result = app(NotificationRepositoryInterface::class)->markAsRead((int) $user->id, (int) $notification->id);

        $this->assertNotNull($result);
        $this->assertTrue($result->is_read);
        $this->assertNotNull($result->read_at);
    }

    public function test_mark_all_notifications_as_read_updates_boolean_column_on_pgsql(): void
    {
        if (config('database.default') !== 'pgsql') {
            $this->markTestSkipped('PostgreSQL connection required for driver-specific assertion.');
        }

        $user = User::query()->first();
        if (! $user) {
            $this->markTestSkipped('No users available in the connected database.');
        }

        DB::table('notifications')
            ->where('user_id', $user->id)
            ->update(['is_read' => DB::raw('false'), 'read_at' => null]);

        $updated = app(NotificationRepositoryInterface::class)->markAllAsRead((int) $user->id);

        $this->assertGreaterThanOrEqual(0, $updated);
        $this->assertSame(
            0,
            DB::table('notifications')
                ->where('user_id', $user->id)
                ->whereRaw('is_read IS FALSE')
                ->count()
        );
    }
}

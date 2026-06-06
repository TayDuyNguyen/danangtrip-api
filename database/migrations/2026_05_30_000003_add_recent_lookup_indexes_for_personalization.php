<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Speeds up recent favorite lookups used by personalized recommendations.
        DB::statement('CREATE INDEX IF NOT EXISTS favorites_user_created_at_idx ON favorites (user_id, created_at DESC)');
        DB::statement('CREATE INDEX IF NOT EXISTS favorites_user_location_recent_idx ON favorites (user_id, location_id, created_at DESC) WHERE location_id IS NOT NULL');
        DB::statement('CREATE INDEX IF NOT EXISTS favorites_user_tour_recent_idx ON favorites (user_id, tour_id, created_at DESC) WHERE tour_id IS NOT NULL');

        // Speeds up recent view lookups for locations/tours by user.
        DB::statement('CREATE INDEX IF NOT EXISTS views_user_created_at_idx ON views (user_id, created_at DESC)');
        DB::statement('CREATE INDEX IF NOT EXISTS views_user_location_recent_idx ON views (user_id, location_id, created_at DESC) WHERE location_id IS NOT NULL');
        DB::statement('CREATE INDEX IF NOT EXISTS views_user_tour_recent_idx ON views (user_id, tour_id, created_at DESC) WHERE tour_id IS NOT NULL');

        // Speeds up recent bookings by user, especially recommendation hydration.
        DB::statement('CREATE INDEX IF NOT EXISTS bookings_user_created_at_idx ON bookings (user_id, created_at DESC)');
        DB::statement('CREATE INDEX IF NOT EXISTS bookings_user_booked_at_idx ON bookings (user_id, booked_at DESC)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS favorites_user_created_at_idx');
        DB::statement('DROP INDEX IF EXISTS favorites_user_location_recent_idx');
        DB::statement('DROP INDEX IF EXISTS favorites_user_tour_recent_idx');

        DB::statement('DROP INDEX IF EXISTS views_user_created_at_idx');
        DB::statement('DROP INDEX IF EXISTS views_user_location_recent_idx');
        DB::statement('DROP INDEX IF EXISTS views_user_tour_recent_idx');

        DB::statement('DROP INDEX IF EXISTS bookings_user_created_at_idx');
        DB::statement('DROP INDEX IF EXISTS bookings_user_booked_at_idx');
    }
};

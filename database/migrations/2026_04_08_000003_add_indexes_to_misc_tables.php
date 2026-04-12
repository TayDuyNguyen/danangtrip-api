<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // favorites: unique (user_id, location_id) + created_at index
        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS favorites_user_location_unique ON favorites (user_id, location_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS favorites_created_at_index ON favorites (created_at)');

        // notifications: indexes for common filters
        DB::statement('CREATE INDEX IF NOT EXISTS notifications_user_id_index ON notifications (user_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS notifications_is_read_index ON notifications (is_read)');
        DB::statement('CREATE INDEX IF NOT EXISTS notifications_created_at_index ON notifications (created_at)');

        // ratings: indexes for location/user/status/date filters
        DB::statement('CREATE INDEX IF NOT EXISTS ratings_location_id_index ON ratings (location_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS ratings_user_id_index ON ratings (user_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS ratings_status_index ON ratings (status)');
        DB::statement('CREATE INDEX IF NOT EXISTS ratings_created_at_index ON ratings (created_at)');

        // pivot: location_tags unique composite and created_at
        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS location_tags_location_tag_unique ON location_tags (location_id, tag_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS location_tags_created_at_index ON location_tags (created_at)');

        // pivot: location_amenities unique composite and created_at
        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS location_amenities_location_amenity_unique ON location_amenities (location_id, amenity_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS location_amenities_created_at_index ON location_amenities (created_at)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS favorites_user_location_unique');
        DB::statement('DROP INDEX IF EXISTS favorites_created_at_index');

        DB::statement('DROP INDEX IF EXISTS notifications_user_id_index');
        DB::statement('DROP INDEX IF EXISTS notifications_is_read_index');
        DB::statement('DROP INDEX IF EXISTS notifications_created_at_index');

        DB::statement('DROP INDEX IF EXISTS ratings_location_id_index');
        DB::statement('DROP INDEX IF EXISTS ratings_user_id_index');
        DB::statement('DROP INDEX IF EXISTS ratings_status_index');
        DB::statement('DROP INDEX IF EXISTS ratings_created_at_index');

        DB::statement('DROP INDEX IF EXISTS location_tags_location_tag_unique');
        DB::statement('DROP INDEX IF EXISTS location_tags_created_at_index');

        DB::statement('DROP INDEX IF EXISTS location_amenities_location_amenity_unique');
        DB::statement('DROP INDEX IF EXISTS location_amenities_created_at_index');
    }
};

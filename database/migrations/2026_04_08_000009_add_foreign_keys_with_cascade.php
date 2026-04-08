<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Helper to add FK if not exists and table exists
        $this->addFkIfNotExists('favorites', 'favorites_user_id_fkey', 'user_id', 'users', 'id', 'CASCADE');
        $this->addFkIfNotExists('favorites', 'favorites_location_id_fkey', 'location_id', 'locations', 'id', 'CASCADE');

        $this->addFkIfNotExists('notifications', 'notifications_user_id_fkey', 'user_id', 'users', 'id', 'CASCADE');

        $this->addFkIfNotExists('rating_images', 'rating_images_rating_id_fkey', 'rating_id', 'ratings', 'id', 'CASCADE');

        $this->addFkIfNotExists('location_tags', 'location_tags_location_id_fkey', 'location_id', 'locations', 'id', 'CASCADE');
        $this->addFkIfNotExists('location_tags', 'location_tags_tag_id_fkey', 'tag_id', 'tags', 'id', 'CASCADE');

        $this->addFkIfNotExists('location_amenities', 'location_amenities_location_id_fkey', 'location_id', 'locations', 'id', 'CASCADE');
        $this->addFkIfNotExists('location_amenities', 'location_amenities_amenity_id_fkey', 'amenity_id', 'amenities', 'id', 'CASCADE');

        $this->addFkIfNotExists('payments', 'payments_booking_id_fkey', 'booking_id', 'bookings', 'id', 'CASCADE');
        $this->addFkIfNotExists('bookings', 'bookings_user_id_fkey', 'user_id', 'users', 'id', 'CASCADE');

        $this->addFkIfNotExists('booking_items', 'booking_items_booking_id_fkey', 'booking_id', 'bookings', 'id', 'CASCADE');
        $this->addFkIfNotExists('booking_items', 'booking_items_tour_id_fkey', 'tour_id', 'tours', 'id', 'CASCADE');
        $this->addFkIfNotExists('booking_items', 'booking_items_tour_schedule_id_fkey', 'tour_schedule_id', 'tour_schedules', 'id', 'CASCADE');
    }

    private function addFkIfNotExists(string $table, string $constraint, string $column, string $refTable, string $refColumn, string $onDelete = 'NO ACTION'): void
    {
        $sql = <<<SQL
DO $$
BEGIN
    IF EXISTS (SELECT 1 FROM pg_class WHERE relname = '{$table}')
       AND NOT EXISTS (
           SELECT 1 FROM pg_constraint WHERE conname = '{$constraint}'
       ) THEN
        ALTER TABLE {$table}
            ADD CONSTRAINT {$constraint}
            FOREIGN KEY ({$column}) REFERENCES {$refTable}({$refColumn}) ON DELETE {$onDelete};
    END IF;
END $$;
SQL;
        DB::statement($sql);
    }

    public function down(): void
    {
        $this->dropFkIfExists('favorites', 'favorites_user_id_fkey');
        $this->dropFkIfExists('favorites', 'favorites_location_id_fkey');

        $this->dropFkIfExists('notifications', 'notifications_user_id_fkey');

        $this->dropFkIfExists('rating_images', 'rating_images_rating_id_fkey');

        $this->dropFkIfExists('location_tags', 'location_tags_location_id_fkey');
        $this->dropFkIfExists('location_tags', 'location_tags_tag_id_fkey');

        $this->dropFkIfExists('location_amenities', 'location_amenities_location_id_fkey');
        $this->dropFkIfExists('location_amenities', 'location_amenities_amenity_id_fkey');

        $this->dropFkIfExists('payments', 'payments_booking_id_fkey');
        $this->dropFkIfExists('bookings', 'bookings_user_id_fkey');

        $this->dropFkIfExists('booking_items', 'booking_items_booking_id_fkey');
        $this->dropFkIfExists('booking_items', 'booking_items_tour_id_fkey');
        $this->dropFkIfExists('booking_items', 'booking_items_tour_schedule_id_fkey');
    }

    private function dropFkIfExists(string $table, string $constraint): void
    {
        $sql = <<<SQL
DO $$
BEGIN
    IF EXISTS (SELECT 1 FROM pg_constraint WHERE conname = '{$constraint}') THEN
        ALTER TABLE {$table} DROP CONSTRAINT {$constraint};
    END IF;
END $$;
SQL;
        DB::statement($sql);
    }
};

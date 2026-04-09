<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS bookings_booking_code_unique ON bookings (booking_code)');
        DB::statement('CREATE INDEX IF NOT EXISTS bookings_user_id_index ON bookings (user_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS bookings_booking_status_index ON bookings (booking_status)');
        DB::statement('CREATE INDEX IF NOT EXISTS bookings_payment_status_index ON bookings (payment_status)');
        DB::statement('CREATE INDEX IF NOT EXISTS bookings_booked_at_index ON bookings (booked_at)');
        DB::statement('CREATE INDEX IF NOT EXISTS bookings_created_at_index ON bookings (created_at)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS bookings_booking_code_unique');
        DB::statement('DROP INDEX IF EXISTS bookings_user_id_index');
        DB::statement('DROP INDEX IF EXISTS bookings_booking_status_index');
        DB::statement('DROP INDEX IF EXISTS bookings_payment_status_index');
        DB::statement('DROP INDEX IF EXISTS bookings_booked_at_index');
        DB::statement('DROP INDEX IF EXISTS bookings_created_at_index');
    }
};

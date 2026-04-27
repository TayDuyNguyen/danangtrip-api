<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tour_schedules', function (Blueprint $table) {
            $table->string('booking_availability', 20)
                ->default('open')
                ->after('status')
                ->index();
        });

        // Backfill from legacy status/full and current capacity.
        DB::statement("
            UPDATE tour_schedules
            SET booking_availability = CASE
                WHEN status = 'full' OR booked_people >= max_people THEN 'sold_out'
                ELSE 'open'
            END
        ");

        // Normalize legacy status 'full' into visible status.
        DB::statement("
            UPDATE tour_schedules
            SET status = 'available'
            WHERE status = 'full'
        ");
    }

    public function down(): void
    {
        Schema::table('tour_schedules', function (Blueprint $table) {
            $table->dropColumn('booking_availability');
        });
    }
};

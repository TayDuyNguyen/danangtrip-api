<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tour_schedules', function (Blueprint $table) {
            $table->string('departure_code', 50)->nullable()->after('status');
            $table->string('departure_place', 255)->nullable()->after('departure_code');
            $table->dateTime('booking_deadline')->nullable()->after('departure_place');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tour_schedules', function (Blueprint $table) {
            $table->dropColumn(['departure_code', 'departure_place', 'booking_deadline']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Adjust monetary columns to DECIMAL(12,2) for better precision
        DB::statement('ALTER TABLE payments ALTER COLUMN amount TYPE NUMERIC(12,2)');

        DB::statement('ALTER TABLE bookings ALTER COLUMN total_amount TYPE NUMERIC(12,2)');
        DB::statement('ALTER TABLE bookings ALTER COLUMN discount_amount TYPE NUMERIC(12,2)');
        DB::statement('ALTER TABLE bookings ALTER COLUMN final_amount TYPE NUMERIC(12,2)');
        DB::statement('ALTER TABLE bookings ALTER COLUMN deposit_amount TYPE NUMERIC(12,2)');
    }

    public function down(): void
    {
        // Revert back to NUMERIC without scale info (best-effort, depends on original schema)
        DB::statement('ALTER TABLE payments ALTER COLUMN amount TYPE NUMERIC(12,0)');

        DB::statement('ALTER TABLE bookings ALTER COLUMN total_amount TYPE NUMERIC(12,0)');
        DB::statement('ALTER TABLE bookings ALTER COLUMN discount_amount TYPE NUMERIC(12,0)');
        DB::statement('ALTER TABLE bookings ALTER COLUMN final_amount TYPE NUMERIC(12,0)');
        DB::statement('ALTER TABLE bookings ALTER COLUMN deposit_amount TYPE NUMERIC(12,0)');
    }
};

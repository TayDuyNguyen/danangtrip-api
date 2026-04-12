<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Adjust location price columns to DECIMAL(12,2)
        DB::statement('ALTER TABLE locations ALTER COLUMN price_min TYPE NUMERIC(12,2)');
        DB::statement('ALTER TABLE locations ALTER COLUMN price_max TYPE NUMERIC(12,2)');
    }

    public function down(): void
    {
        // Revert to NUMERIC without scale (best-effort)
        DB::statement('ALTER TABLE locations ALTER COLUMN price_min TYPE NUMERIC(12,0)');
        DB::statement('ALTER TABLE locations ALTER COLUMN price_max TYPE NUMERIC(12,0)');
    }
};
